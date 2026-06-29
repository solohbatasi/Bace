<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lesson_ticket_rules')) {
            Schema::create('lesson_ticket_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->restrictOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained()->restrictOnDelete();
                $table->string('target_type', 30)->default('course');
                $table->string('name');
                $table->string('session_type', 40);
                $table->unsignedSmallInteger('lesson_count');
                $table->unsignedSmallInteger('lessons_per_ticket')->default(1);
                $table->string('pricing_type', 30);
                $table->decimal('pricing_value', 12, 2);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->softDeletesTz();
                $table->timestampsTz();

                $table->index(['course_id', 'target_type']);
                $table->index(['unit_id', 'session_type']);
                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('lesson_tickets')) {
            Schema::create('lesson_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lesson_ticket_rule_id')->constrained()->restrictOnDelete();
                $table->foreignId('student_id')->constrained()->restrictOnDelete();
                $table->foreignId('course_id')->constrained()->restrictOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained()->restrictOnDelete();
                $table->string('target_type', 30)->default('course');
                $table->string('ticket_number', 80)->unique();
                $table->string('verification_token', 80)->unique();
                $table->string('session_type', 40);
                $table->unsignedSmallInteger('lesson_count');
                $table->decimal('amount_required', 12, 2);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->date('issued_on');
                $table->timestampTz('downloaded_at')->nullable();
                $table->string('status', 30)->default('issued');
                $table->text('notes')->nullable();
                $this->auditColumns($table);
                $table->softDeletesTz();
                $table->timestampsTz();

                $table->index(['student_id', 'course_id']);
                $table->index(['unit_id', 'status']);
                $table->index(['lesson_ticket_rule_id', 'status']);
                $table->index('issued_on');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_tickets');
        Schema::dropIfExists('lesson_ticket_rules');
    }

    private function auditColumns(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
    }
};
