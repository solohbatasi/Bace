<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUSES = ['draft', 'published', 'closed'];

    protected $guarded = [];

    protected $casts = [
        'opens_at' => 'datetime',
        'due_at' => 'datetime',
        'max_score' => 'decimal:2',
    ];

    public function lecturerUnitAssignment(): BelongsTo
    {
        return $this->belongsTo(LecturerUnitAssignment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
