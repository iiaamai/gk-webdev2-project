<?php

namespace App\Support\Cloudinary;

final readonly class CloudinaryCredentials
{
    public function __construct(
        public string $cloudName,
        public string $apiKey,
        public string $apiSecret,
        public string $folder,
    ) {}

    public static function fromConfig(): ?self
    {
        $url = config('filesystems.disks.cloudinary.url');
        $cloudName = config('filesystems.disks.cloudinary.cloud_name');
        $apiKey = config('filesystems.disks.cloudinary.api_key');
        $apiSecret = config('filesystems.disks.cloudinary.api_secret');
        $folder = (string) config('filesystems.disks.cloudinary.folder', 'gk-trucking');

        if (is_string($url) && str_starts_with($url, 'cloudinary://')) {
            $parsed = parse_url($url);

            if (is_array($parsed)) {
                $cloudName = $parsed['host'] ?? $cloudName;
                $apiKey = isset($parsed['user']) ? rawurldecode($parsed['user']) : $apiKey;
                $apiSecret = isset($parsed['pass']) ? rawurldecode($parsed['pass']) : $apiSecret;
            }
        }

        if (! filled($cloudName) || ! filled($apiKey) || ! filled($apiSecret)) {
            return null;
        }

        return new self(
            cloudName: (string) $cloudName,
            apiKey: (string) $apiKey,
            apiSecret: (string) $apiSecret,
            folder: trim($folder, '/'),
        );
    }

    public static function isConfigured(): bool
    {
        return self::fromConfig() !== null;
    }
}
