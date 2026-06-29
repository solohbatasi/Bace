<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lesson_ticket_checkins')) {
            Schema::create('lesson_ticket_checkins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lesson_ticket_id')->constrained()->restrictOnDelete();
                $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestampTz('checked_in_at');
                $table->string('status', 30)->default('checked_in');
                $table->text('notes')->nullable();
                $table->timestampsTz();

                $table->index(['lesson_ticket_id', 'checked_in_at']);
                $table->index('checked_in_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_ticket_checkins');
    }
};
