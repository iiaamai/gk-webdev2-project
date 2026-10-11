<?php

namespace App\Actions;

use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
            Storage::disk('local')->delete($previous);
        }

        Storage::disk('local')->putFileAs($directory, $file, $filename);
        Setting::setValue('qr_ph_path', $path);

        $this->activityLogger->log(
            action: 'settings.qr_ph_updated',
            description: 'Company QR Ph image updated.',
            properties: ['path' => $path],
        );

        return $path;
    }
}
