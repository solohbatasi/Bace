<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 40)->nullable();
            $table->string('name');
            $table->string('scope_type', 30)->default('permanent');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('weight_percent', 5, 2)->nullable();
            $table->boolean('is_analysed')->default(false);
            $table->boolean('include_in_final_analysis')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['course_id', 'scope_type']);
            $table->index(['unit_id', 'scope_type']);
            $table->index(['academic_year_id', 'semester_id']);
            $table->index('is_active');
        });

        DB::statement('ALTER TABLE examinations ADD CONSTRAINT examinations_owner_check CHECK ((course_id IS NOT NULL AND unit_id IS NULL) OR (course_id IS NULL AND unit_id IS NOT NULL))');
        DB::statement("ALTER TABLE examinations ADD CONSTRAINT examinations_scope_type_check CHECK (scope_type IN ('permanent', 'semester', 'period'))");
        DB::statement("ALTER TABLE examinations ADD CONSTRAINT examinations_scope_dates_check CHECK ((scope_type = 'permanent' AND academic_year_id IS NULL AND semester_id IS NULL AND starts_on IS NULL AND ends_on IS NULL) OR (scope_type = 'semester' AND academic_year_id IS NOT NULL AND semester_id IS NOT NULL) OR (scope_type = 'period' AND starts_on IS NOT NULL AND ends_on IS NOT NULL AND starts_on <= ends_on))");
    }

    public function down(): void
    {
        Schema::dropIfExists('examinations');
    }
};
