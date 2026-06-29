<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonTicketCheckin extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(LessonTicket::class, 'lesson_ticket_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
