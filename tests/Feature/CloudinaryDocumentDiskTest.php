<?php

namespace Tests\Feature;

use App\Support\Cloudinary\CloudinaryAdapter;
use App\Support\Cloudinary\CloudinaryCredentials;
use App\Support\DocumentStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudinaryDocumentDiskTest extends TestCase
{
    public function test_document_storage_falls_back_to_local_when_cloudinary_is_not_configured(): void
    {
        config([
            'filesystems.default' => 'cloudinary',
            'filesystems.disks.cloudinary.url' => null,
            'filesystems.disks.cloudinary.cloud_name' => null,
            'filesystems.disks.cloudinary.api_key' => null,
            'filesystems.disks.cloudinary.api_secret' => null,
        ]);

        $this->assertSame('local', DocumentStorage::diskName());
    }

    public function test_cloudinary_disk_uploads_reads_and_deletes_authenticated_assets(): void
    {
        config([
            'filesystems.default' => 'cloudinary',
            'filesystems.disks.cloudinary.url' => 'cloudinary://api-key:api-secret@demo-cloud',
            'filesystems.disks.cloudinary.folder' => 'gk-trucking',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/image/upload')) {
                return Http::response(['public_id' => 'gk-trucking/bookings/GK-2026-0001/gatepass'], 200);
            }

            if (str_contains($url, '/image/destroy')) {
                return Http::response(['result' => 'ok'], 200);
            }

            if (str_contains($url, '/resources/image/authenticated')) {
                return Http::response([
                    'version' => 1_753_862_893,
                    'format' => 'png',
                ], 200);
            }

            if (str_contains($url, 'res.cloudinary.com')) {
                return Http::response('png-bytes', 200);
            }

            return Http::response('unexpected '.$url, 500);
        });

        $path = 'bookings/GK-2026-0001/gatepass.png';
        $disk = Storage::disk('cloudinary');

        $this->assertTrue($disk->put($path, 'png-bytes'));
        $this->assertTrue($disk->exists($path));
        $this->assertSame('png-bytes', $disk->get($path));
        $this->assertTrue($disk->delete($path));

        Http::assertSent(function ($request) {
            $body = $request->body();

            return str_contains($request->url(), '/image/upload')
                && str_contains($body, 'authenticated')
                && str_contains($body, 'gk-trucking/bookings/GK-2026-0001/gatepass');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'res.cloudinary.com')
                && str_contains($request->url(), '/image/authenticated/s--')
                && str_contains($request->url(), 'gk-trucking/bookings/GK-2026-0001/gatepass.png');
        });
    }

    public function test_delivery_url_signature_matches_cloudinary_documentation_example(): void
    {
        $adapter = new CloudinaryAdapter(
            new CloudinaryCredentials(
                cloudName: 'demo',
                apiKey: 'key',
                apiSecret: 'abcd',
                folder: '',
            ),
        );

        $reflection = new \ReflectionClass($adapter);
        $method = $reflection->getMethod('deliveryUrlSignature');
        $method->setAccessible(true);

        $this->assertSame(
            'iDy_JeBq',
            $method->invoke($adapter, 'c_fill,w_300,h_250/e_grayscale/sample-authenticated.png'),
        );
    }

    public function test_document_storage_uses_cloudinary_when_credentials_are_present(): void
    {
        config([
            'filesystems.default' => 'local',
            'filesystems.disks.cloudinary.url' => 'cloudinary://api-key:api-secret@demo-cloud',
        ]);

        $this->assertSame('cloudinary', DocumentStorage::diskName());
    }

    public function test_explicit_cloudinary_keys_override_a_malformed_url(): void
    {
        config([
            'filesystems.disks.cloudinary.url' => 'cloudinary://WRONGKEY:url-secret@url-cloud',
            'filesystems.disks.cloudinary.cloud_name' => 'demo-cloud',
            'filesystems.disks.cloudinary.api_key' => 'api-key',
            'filesystems.disks.cloudinary.api_secret' => 'api-secret',
        ]);

        $credentials = CloudinaryCredentials::fromConfig();

        $this->assertNotNull($credentials);
        $this->assertSame('demo-cloud', $credentials->cloudName);
        $this->assertSame('api-key', $credentials->apiKey);
        $this->assertSame('api-secret', $credentials->apiSecret);
    }
}
