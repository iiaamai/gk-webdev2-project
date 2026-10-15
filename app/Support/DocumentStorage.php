<?php

namespace App\Support;

use App\Support\Cloudinary\CloudinaryCredentials;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class DocumentStorage
{
    public static function diskName(): string
    {
        return CloudinaryCredentials::isConfigured() ? 'cloudinary' : 'local';
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }
}
