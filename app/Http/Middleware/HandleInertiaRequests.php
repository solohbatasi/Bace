<?php

namespace App\Http\Middleware;

use App\Models\OrganisationSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user(),
                'permissions' => fn () => $request->user()
                    ? $request->user()
                        ->loadMissing(['permissions:id,name', 'roles.permissions:id,name'])
                        ->permissions
                        ->pluck('name')
                        ->merge($request->user()->roles->flatMap->permissions->pluck('name'))
                        ->unique()
                        ->values()
                    : [],
            ],
            'organisation' => fn () => optional(OrganisationSetting::query()->first())->only([
                'name',
                'short_name',
                'logo_url',
                'official_email',
                'primary_contact',
                'location',
            ]) ?? [
                'name' => config('app.name'),
                'short_name' => 'ISP',
                'logo_url' => null,
                'official_email' => null,
                'primary_contact' => null,
                'location' => null,
            ],
        ];
    }
}
