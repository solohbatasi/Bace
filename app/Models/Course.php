<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'parent_course_id' => 'integer',
        'duration_semesters' => 'integer',
        'fees' => 'decimal:2',
        'has_units' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function parentCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'parent_course_id');
    }

    public function subcourses(): HasMany
    {
        return $this->hasMany(Course::class, 'parent_course_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CollegeClass::class, 'course_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scoreLevels(): HasMany
    {
        return $this->hasMany(ScoreLevel::class)->orderBy('sort_order')->orderBy('min_score');
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(Examination::class);
    }

    public function subcourseExaminations(): HasMany
    {
        return $this->hasMany(Examination::class, 'subcourse_id');
    }
}
