<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganisationSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'operation_hours' => 'array',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? '/storage/'.$this->logo_path : null;
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'name' => config('app.name'),
            'short_name' => 'ISP',
            'operation_hours' => static::defaultOperationHours(),
        ]);
    }

    public static function defaultOperationHours(): array
    {
        return collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
            ->map(fn (string $day) => [
                'day' => $day,
                'is_open' => ! in_array($day, ['Saturday', 'Sunday'], true),
                'opens_at' => '08:00',
                'closes_at' => '17:00',
            ])
            ->all();
    }
}
