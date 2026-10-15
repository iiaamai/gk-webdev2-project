<?php

namespace App\Support\Cloudinary;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Throwable;

class CloudinaryAdapter implements FilesystemAdapter
{
    public function __construct(
        private readonly CloudinaryCredentials $credentials,
    ) {}

    public function fileExists(string $path): bool
    {
        try {
            $response = $this->http()
                ->withBasicAuth($this->credentials->apiKey, $this->credentials->apiSecret)
                ->get($this->adminResourceUrl($path));

            return $response->successful();
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $timestamp = (string) time();
        $publicId = $this->publicId($path);
        $params = [
            'overwrite' => 'true',
            'public_id' => $publicId,
            'timestamp' => $timestamp,
            'type' => 'authenticated',
        ];
        $params['signature'] = $this->sign($params);
        $params['api_key'] = $this->credentials->apiKey;

        try {
            $response = $this->http()
                ->attach('file', $contents, basename($path))
                ->post($this->uploadUrl(), $params);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }

        if (! $response->successful()) {
            throw UnableToWriteFile::atLocation($path, $response->body());
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents) ?: '', $config);
    }

    public function read(string $path): string
    {
        try {
            $resource = $this->http()
                ->withBasicAuth($this->credentials->apiKey, $this->credentials->apiSecret)
                ->get($this->adminResourceUrl($path));

            if (! $resource->successful()) {
                throw UnableToReadFile::fromLocation($path, $resource->body());
            }

            $version = $resource->json('version');
            $format = $resource->json('format');
            $format = is_string($format) && $format !== ''
                ? ($format === 'jpeg' ? 'jpg' : $format)
                : $this->format($path);

            $versionNumber = is_numeric($version) ? (int) $version : null;

            $url = $this->signedDeliveryUrl($path, $versionNumber, $format);

            $download = $this->http()->get($url);
        } catch (UnableToReadFile $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }

        if (! $download->successful()) {
            throw UnableToReadFile::fromLocation($path, $download->body());
        }

        return $download->body();
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, 'Unable to open a temporary stream.');
        }

        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        $timestamp = (string) time();
        $params = [
            'public_id' => $this->publicId($path),
            'timestamp' => $timestamp,
            'type' => 'authenticated',
        ];
        $params['signature'] = $this->sign($params);
        $params['api_key'] = $this->credentials->apiKey;

        try {
            $response = $this->http()->asForm()->post($this->destroyUrl(), $params);
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }

        if (! $response->successful()) {
            throw UnableToDeleteFile::atLocation($path, $response->body());
        }
    }

    public function deleteDirectory(string $path): void
    {
        // Cloudinary folders are implicit in public IDs.
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Cloudinary folders are implicit in public IDs.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Documents stay authenticated and are served through app ACL.
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: Visibility::PRIVATE);
    }

    public function mimeType(string $path): FileAttributes
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => throw UnableToRetrieveMetadata::mimeType($path),
        };

        return new FileAttributes($path, mimeType: $mime);
    }

    public function lastModified(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->write($destination, $this->read($source), $config);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function http(): PendingRequest
    {
        return Http::connectTimeout(5)->timeout(20);
    }

    /**
     * @param  array<string, string>  $params
     */
    private function sign(array $params): string
    {
        ksort($params);
        $pairs = [];

        foreach ($params as $key => $value) {
            if (in_array($key, ['file', 'cloud_name', 'resource_type', 'api_key'], true)) {
                continue;
            }

            if ($value === '') {
                continue;
            }

            $pairs[] = $key.'='.$value;
        }

        return sha1(implode('&', $pairs).$this->credentials->apiSecret);
    }

    private function publicId(string $path): string
    {
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        $withoutExtension = preg_replace('/\.[^.]+$/', '', $relative) ?: $relative;
        $folder = $this->credentials->folder;

        if ($folder === '') {
            return $withoutExtension;
        }

        return $folder.'/'.$withoutExtension;
    }

    private function format(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    private function uploadUrl(): string
    {
        return 'https://api.cloudinary.com/v1_1/'.$this->credentials->cloudName.'/image/upload';
    }

    private function destroyUrl(): string
    {
        return 'https://api.cloudinary.com/v1_1/'.$this->credentials->cloudName.'/image/destroy';
    }

    private function adminResourceUrl(string $path): string
    {
        return 'https://api.cloudinary.com/v1_1/'.$this->credentials->cloudName
            .'/resources/image/authenticated/'.rawurlencode($this->publicId($path));
    }

    private function signedDeliveryUrl(string $path, ?int $version = null, ?string $format = null): string
    {
        $publicId = $this->publicId($path);
        $format ??= $this->format($path);
        $deliverySuffix = $publicId.($format !== '' ? '.'.$format : '');

        $pathAfterSignature = $version !== null
            ? 'v'.$version.'/'.$deliverySuffix
            : $deliverySuffix;

        $signature = $this->deliveryUrlSignature($pathAfterSignature);

        return 'https://res.cloudinary.com/'.$this->credentials->cloudName
            .'/image/authenticated/s--'.$signature.'--/'.$pathAfterSignature;
    }

    private function deliveryUrlSignature(string $toSign): string
    {
        $digest = sha1($toSign.$this->credentials->apiSecret, true);
        $encoded = rtrim(strtr(base64_encode($digest), '+/', '-_'), '=');

        return substr($encoded, 0, 8);
    }
}
