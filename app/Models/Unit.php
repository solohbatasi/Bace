<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_core' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturerAssignments(): HasMany
    {
        return $this->hasMany(LecturerUnitAssignment::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function scoreLevels(): HasMany
    {
        return $this->hasMany(ScoreLevel::class)->orderBy('sort_order')->orderBy('min_score');
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(Examination::class);
    }

    public function lessonTicketRules(): HasMany
    {
        return $this->hasMany(LessonTicketRule::class);
    }

    public function lessonTickets(): HasMany
    {
        return $this->hasMany(LessonTicket::class);
    }
}
