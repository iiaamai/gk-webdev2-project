<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\EarningsController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterCustomerController;
use App\Http\Controllers\Auth\RegisterDriverController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BookingDocumentController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Customer\BookingRatingController;
use App\Http\Controllers\Customer\OverviewController as CustomerOverviewController;
use App\Http\Controllers\Driver\DeliveryController as DriverDeliveryController;
use App\Http\Controllers\Driver\OverviewController as DriverOverviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\OverviewController as StaffOverviewController;
use App\Http\Controllers\Staff\VehicleController as StaffVehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('register/customer', [RegisterCustomerController::class, 'create'])->name('register.customer');
    Route::post('register/customer', [RegisterCustomerController::class, 'store']);

    Route::get('register/driver', [RegisterDriverController::class, 'create'])->name('register.driver');
    Route::post('register/driver', [RegisterDriverController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', LogoutController::class)->name('logout');

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified', 'not_archived'])->group(function (): void {
    Route::get('documents/bookings/{booking}/gatepass', [BookingDocumentController::class, 'gatepass'])
        ->name('documents.bookings.gatepass');
    Route::get('documents/bookings/{booking}/eir', [BookingDocumentController::class, 'eir'])
        ->name('documents.bookings.eir');
    Route::get('documents/bookings/{booking}/pod/photos/{index}', [BookingDocumentController::class, 'podPhoto'])
        ->whereNumber('index')
        ->name('documents.bookings.pod.photo');
    Route::get('users/{user}/avatar', [ProfileController::class, 'avatar'])
        ->name('users.avatar');
    Route::put('profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::middleware('role:'.UserRole::Customer->value)
        ->prefix('customer')
        ->name('customer.')
        ->group(function (): void {
            Route::get('/', CustomerOverviewController::class)->name('home');
            Route::view('settings', 'customer.settings')->name('settings.edit');
            Route::resource('bookings', CustomerBookingController::class)->only(['index', 'create', 'store', 'show']);
            Route::get('bookings/{booking}/receipt', [CustomerBookingController::class, 'downloadReceipt'])
                ->name('bookings.receipt');
            Route::post('bookings/{booking}/rating', [BookingRatingController::class, 'store'])
                ->name('bookings.rating.store');
        });

    Route::middleware('role:'.UserRole::Driver->value)
        ->prefix('driver')
        ->name('driver.')
        ->group(function (): void {
            Route::get('/', DriverOverviewController::class)->name('home');

            Route::view('settings', 'driver.settings')->name('settings.edit');

            Route::get('deliveries', [DriverDeliveryController::class, 'index'])->name('deliveries.index');
            Route::get('deliveries/{booking}', [DriverDeliveryController::class, 'show'])->name('deliveries.show');
            Route::post('deliveries/{booking}/accept', [DriverDeliveryController::class, 'accept'])->name('deliveries.accept');
            Route::patch('deliveries/{booking}/status', [DriverDeliveryController::class, 'updateStatus'])
                ->name('deliveries.status.update');
            Route::post('deliveries/{booking}/eir', [DriverDeliveryController::class, 'storeEir'])
                ->name('deliveries.eir.store');
            Route::post('deliveries/{booking}/pod', [DriverDeliveryController::class, 'storePod'])
                ->name('deliveries.pod.store');
            Route::get('deliveries/{booking}/receipt', [DriverDeliveryController::class, 'downloadReceipt'])
                ->name('deliveries.receipt');
        });

    Route::middleware('role:'.UserRole::Staff->value)
        ->prefix('staff')
        ->name('staff.')
        ->group(function (): void {
            Route::get('/', StaffOverviewController::class)->name('home');

            Route::view('settings', 'staff.settings')->name('settings.edit');

            Route::resource('bookings', StaffBookingController::class)->only(['index', 'show', 'edit', 'update']);
            Route::post('bookings/{booking}/gatepass', [StaffBookingController::class, 'storeGatepass'])
                ->name('bookings.gatepass.store');
            Route::post('bookings/{booking}/cancel', [StaffBookingController::class, 'cancel'])
                ->name('bookings.cancel');
            Route::post('bookings/{booking}/invoice/mark-paid', [StaffBookingController::class, 'markInvoicePaid'])
                ->name('bookings.invoice.mark-paid');
            Route::get('bookings/{booking}/receipt', [StaffBookingController::class, 'downloadReceipt'])
                ->name('bookings.receipt');

            Route::resource('fleet', StaffVehicleController::class)
                ->parameters(['fleet' => 'vehicle'])
                ->only(['index', 'edit', 'update']);
        });

    Route::middleware('role:'.UserRole::SystemAdmin->value)
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', OverviewController::class)->name('home');

            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

            Route::resource('pricing', PricingController::class)->except(['show']);
            Route::resource('fleet', VehicleController::class)
                ->parameters(['fleet' => 'vehicle'])
                ->except(['show']);
            Route::resource('users', UserController::class);

            Route::resource('bookings', AdminBookingController::class);
            Route::post('bookings/{booking}/gatepass', [AdminBookingController::class, 'storeGatepass'])
                ->name('bookings.gatepass.store');
            Route::patch('bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])
                ->name('bookings.status.update');
            Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])
                ->name('bookings.cancel');
            Route::post('bookings/{booking}/eir', [AdminBookingController::class, 'storeEir'])
                ->name('bookings.eir.store');
            Route::post('bookings/{booking}/pod', [AdminBookingController::class, 'storePod'])
                ->name('bookings.pod.store');
            Route::get('bookings/{booking}/receipt', [AdminBookingController::class, 'downloadReceipt'])
                ->name('bookings.receipt');
            Route::post('bookings/{booking}/invoice/mark-paid', [AdminBookingController::class, 'markInvoicePaid'])
                ->name('bookings.invoice.mark-paid');
            Route::post('bookings/{booking}/invoice/mark-unpaid', [AdminBookingController::class, 'markInvoiceUnpaid'])
                ->name('bookings.invoice.mark-unpaid');

            Route::get('earnings', [EarningsController::class, 'index'])->name('earnings.index');
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        });
});
