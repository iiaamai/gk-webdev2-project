<?php

namespace App\Support;

use App\Support\Cloudinary\CloudinaryCredentials;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class DocumentStorage
{
    public static function diskName(): string
    {
        $disk = (string) config('filesystems.default', 'local');

        if ($disk === 'cloudinary' && ! CloudinaryCredentials::isConfigured()) {
            return 'local';
        }

        return $disk;
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }
}
