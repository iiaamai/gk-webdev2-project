<?php

namespace App\Actions;

use App\Models\User;
use App\Support\DocumentStorage;
use Illuminate\Http\UploadedFile;

class UploadUserAvatar
{
    public function execute(User $user, UploadedFile $file): string
    {
        $directory = 'users/'.$user->id;
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $filename = 'avatar.'.$extension;
        $path = $directory.'/'.$filename;

        if (filled($user->avatar_path)) {
            DocumentStorage::disk()->delete($user->avatar_path);
        }

        DocumentStorage::disk()->putFileAs($directory, $file, $filename);

        $user->forceFill(['avatar_path' => $path])->save();

        return $path;
    }
}
