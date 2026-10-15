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
        $cloudName = self::configString('filesystems.disks.cloudinary.cloud_name');
        $apiKey = self::configString('filesystems.disks.cloudinary.api_key');
        $apiSecret = self::configString('filesystems.disks.cloudinary.api_secret');
        $folder = self::configString('filesystems.disks.cloudinary.folder') ?? 'gk-trucking';
        $url = self::configString('filesystems.disks.cloudinary.url');

        if ($url !== null && str_starts_with($url, 'cloudinary://')) {
            $parsed = parse_url($url);

            if (is_array($parsed)) {
                $cloudName = self::nonEmpty($parsed['host'] ?? null) ?? $cloudName;
                $apiKey = isset($parsed['user'])
                    ? (self::nonEmpty(rawurldecode($parsed['user'])) ?? $apiKey)
                    : $apiKey;
                $apiSecret = isset($parsed['pass'])
                    ? (self::nonEmpty(rawurldecode($parsed['pass'])) ?? $apiSecret)
                    : $apiSecret;
            }
        }

        $cloudName = self::configString('filesystems.disks.cloudinary.cloud_name') ?? $cloudName;
        $apiKey = self::configString('filesystems.disks.cloudinary.api_key') ?? $apiKey;
        $apiSecret = self::configString('filesystems.disks.cloudinary.api_secret') ?? $apiSecret;

        if ($cloudName === null || $apiKey === null || $apiSecret === null) {
            return null;
        }

        return new self(
            cloudName: $cloudName,
            apiKey: $apiKey,
            apiSecret: $apiSecret,
            folder: trim($folder, '/'),
        );
    }

    public static function isConfigured(): bool
    {
        return self::fromConfig() !== null;
    }

    private static function configString(string $key): ?string
    {
        return self::nonEmpty(config($key));
    }

    private static function nonEmpty(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
