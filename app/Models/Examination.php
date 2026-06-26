<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Examination extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const SCOPE_TYPES = ['permanent', 'semester', 'period'];

    protected $guarded = [];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'max_score' => 'decimal:2',
        'weight_percent' => 'decimal:2',
        'is_analysed' => 'boolean',
        'include_in_final_analysis' => 'boolean',
        'can_edit_results' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function scoreLevels(): HasMany
    {
        return $this->hasMany(ScoreLevel::class)->orderBy('sort_order')->orderBy('min_score');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExaminationResult::class);
    }
}
