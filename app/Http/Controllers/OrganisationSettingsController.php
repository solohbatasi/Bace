<?php

namespace App\Http\Controllers;

use App\Models\OrganisationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganisationSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission('organisation-settings.view|classes.manage'), 403);

        return Inertia::render('Admin/OrganisationSettings', [
            'setting' => OrganisationSetting::current(),
            'defaultOperationHours' => OrganisationSetting::defaultOperationHours(),
            'permissions' => [
                'canEdit' => $request->user()->hasAnyPermission('organisation-settings.edit|classes.manage'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('organisation-settings.edit|classes.manage'), 403);

        $setting = OrganisationSetting::current();
        $data = $this->settingData($request);

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('organisation/logos', 'public');
        }

        unset($data['logo']);
        $setting->update($data);

        return back()->with('flash.banner', 'Organisation settings updated.');
    }

    private function settingData(Request $request): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'official_email' => ['nullable', 'email', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'marketing_email' => ['nullable', 'email', 'max:255'],
            'primary_contact' => ['nullable', 'string', 'max:40'],
            'secondary_contact' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:2000'],
            'mission' => ['nullable', 'string', 'max:2000'],
            'vision' => ['nullable', 'string', 'max:2000'],
            'about' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'operation_hours' => ['nullable', 'array'],
            'operation_hours.*.day' => ['required_with:operation_hours', 'string', 'max:20'],
            'operation_hours.*.is_open' => ['boolean'],
            'operation_hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'operation_hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ]);
    }
}
