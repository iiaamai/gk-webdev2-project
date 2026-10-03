<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadUserAvatar
{
    public function execute(User $user, UploadedFile $file): string
    {
        $directory = 'users/'.$user->id;
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $filename = 'avatar.'.$extension;
        $path = $directory.'/'.$filename;

        if (filled($user->avatar_path)) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        Storage::disk('local')->putFileAs($directory, $file, $filename);

        $user->forceFill(['avatar_path' => $path])->save();

        return $path;
    }
}
