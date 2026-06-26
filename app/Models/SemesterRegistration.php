<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SemesterRegistration extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'dropped', 'transferred'];

    protected $guarded = [];

    protected $casts = [
        'registered_at' => 'datetime',
        'approved_at' => 'datetime',
        'course_fee' => 'decimal:2',
        'course_score' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(CollegeClass::class, 'class_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function examinationResults(): HasMany
    {
        return $this->hasMany(ExaminationResult::class);
    }
}
