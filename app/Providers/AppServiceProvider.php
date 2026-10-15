<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Cloudinary\CloudinaryAdapter;
use App\Support\Cloudinary\CloudinaryCredentials;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        Route::bind('archivedBooking', fn (string $value): Booking => Booking::onlyArchived()->findOrFail($value));
        Route::bind('archivedUser', fn (string $value): User => User::onlyArchived()->findOrFail($value));
        Route::bind('archivedVehicle', fn (string $value): Vehicle => Vehicle::onlyArchived()->findOrFail($value));
        Route::bind('archivedPricing', fn (string $value): Pricing => Pricing::onlyArchived()->findOrFail($value));

        Storage::extend('cloudinary', function ($app, array $config): FilesystemAdapter {
            $credentials = CloudinaryCredentials::fromConfig();

            if ($credentials === null) {
                throw new InvalidArgumentException('Cloudinary credentials are not configured.');
            }

            $adapter = new CloudinaryAdapter($credentials);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }
}
