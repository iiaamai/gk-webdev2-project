<?php

namespace App\Actions;

use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Support\DocumentStorage;
use Illuminate\Http\UploadedFile;

class UploadCompanyQrPh
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(UploadedFile $file): string
    {
        $directory = 'settings';
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = 'qr-ph.'.$extension;
        $path = $directory.'/'.$filename;

        $previous = Setting::getValue('qr_ph_path');

        if (filled($previous) && $previous !== $path) {
            DocumentStorage::disk()->delete($previous);
        }

        DocumentStorage::disk()->putFileAs($directory, $file, $filename);
        Setting::setValue('qr_ph_path', $path);

        $this->activityLogger->log(
            action: 'settings.qr_ph_updated',
            description: 'Company QR Ph image updated.',
            properties: ['path' => $path],
        );

        return $path;
    }
}
