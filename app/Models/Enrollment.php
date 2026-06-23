<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'dropped', 'transferred'];

    protected $guarded = [];

    protected $casts = [
        'enrolled_on' => 'date',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(SemesterRegistration::class, 'semester_registration_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(CollegeClass::class, 'class_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
