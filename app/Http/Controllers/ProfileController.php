<?php

namespace App\Http\Controllers;

use App\Actions\UploadUserAvatar;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\RoleHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function update(
        UpdateProfileRequest $request,
        UploadUserAvatar $uploadUserAvatar,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'mobile']));
        $user->save();

        if ($request->hasFile('avatar')) {
            $uploadUserAvatar->execute($user, $request->file('avatar'));
        }

        $activityLogger->log(
            action: 'user.profile_updated',
            subject: $user,
            description: "Profile updated for {$user->email}.",
            user: $user,
            request: $request,
        );

        return redirect()
            ->to($this->settingsUrl($user))
            ->with('status', 'Profile updated.');
    }

    public function avatar(User $user): StreamedResponse
    {
        $this->authorize('viewAvatar', $user);

        abort_unless(filled($user->avatar_path) && Storage::disk('local')->exists($user->avatar_path), 404);

        $mimeType = Storage::disk('local')->mimeType($user->avatar_path) ?: 'application/octet-stream';

        return Storage::disk('local')->response(
            $user->avatar_path,
            'avatar',
            ['Content-Type' => $mimeType],
            'inline',
        );
    }

    private function settingsUrl(User $user): string
    {
        return match (true) {
            $user->isCustomer() => route('customer.settings.edit'),
            $user->isDriver() => route('driver.settings.edit'),
            $user->isStaff() => route('staff.settings.edit'),
            $user->isSystemAdmin() => route('admin.settings.edit'),
            default => RoleHome::path($user),
        };
    }
}
