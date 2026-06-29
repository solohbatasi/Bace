<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonTicket extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'lesson_count' => 'integer',
        'amount_required' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'issued_on' => 'date',
        'downloaded_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(LessonTicketRule::class, 'lesson_ticket_rule_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
