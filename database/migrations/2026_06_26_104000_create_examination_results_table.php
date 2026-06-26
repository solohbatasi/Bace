<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('grade', 20)->nullable();
            $table->string('comment')->nullable();
            $table->timestampTz('recorded_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->unique(['examination_id', 'student_id']);
            $table->index(['semester_registration_id', 'examination_id'], 'exam_results_registration_index');
            $table->index(['enrollment_id', 'examination_id']);
        });

        DB::statement('ALTER TABLE examination_results ADD CONSTRAINT examination_results_score_check CHECK (score IS NULL OR score >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_results');
    }
};
