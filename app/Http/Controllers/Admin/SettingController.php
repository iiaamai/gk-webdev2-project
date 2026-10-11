<?php

namespace App\Http\Controllers\Admin;

use App\Actions\UploadCompanyQrPh;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('viewAny', Setting::class);

        $keys = [
            'company_name',
            'support_email',
            'booking_seq',
            'map_center_lat',
            'map_center_lng',
            'map_zoom',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getValue($key, '');
        }

        $qrPhPath = Setting::getValue('qr_ph_path');

        return view('admin.settings.edit', compact('settings', 'qrPhPath'));
    }

    public function update(UpdateSettingsRequest $request, UploadCompanyQrPh $uploadCompanyQrPh): RedirectResponse
    {
        $validated = $request->safe()->except(['qr_ph']);

        foreach ($validated as $key => $value) {
            Setting::setValue($key, $value === null ? null : (string) $value);
        }

        if ($request->hasFile('qr_ph')) {
            $uploadCompanyQrPh->execute($request->file('qr_ph'));
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Settings saved.');
    }
}
