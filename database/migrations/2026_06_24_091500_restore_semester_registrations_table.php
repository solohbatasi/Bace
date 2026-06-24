<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('semester_registrations')) {
            Schema::create('semester_registrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->restrictOnDelete();
                $table->foreignId('class_id')->constrained()->restrictOnDelete();
                $table->foreignId('semester_id')->constrained()->restrictOnDelete();
                $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
                $table->timestampTz('registered_at');
                $table->timestampTz('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletesTz();
                $table->timestampsTz();

                $table->index(['student_id', 'academic_year_id', 'semester_id']);
                $table->index(['status', 'registered_at']);
            });
        }

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_class
        WHERE relname = 'semester_registrations_student_term_active_unique'
    ) THEN
        CREATE UNIQUE INDEX semester_registrations_student_term_active_unique
        ON semester_registrations (student_id, semester_id, academic_year_id)
        WHERE deleted_at IS NULL;
    END IF;
END
$$;
SQL);

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'enrollments'
          AND column_name = 'semester_registration_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'enrollments_semester_registration_id_foreign'
    ) THEN
        ALTER TABLE enrollments
        ADD CONSTRAINT enrollments_semester_registration_id_foreign
        FOREIGN KEY (semester_registration_id)
        REFERENCES semester_registrations(id)
        ON DELETE SET NULL;
    END IF;
END
$$;
SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'enrollments_semester_registration_id_foreign'
    ) THEN
        ALTER TABLE enrollments DROP CONSTRAINT enrollments_semester_registration_id_foreign;
    END IF;
END
$$;
SQL);

        Schema::dropIfExists('semester_registrations');
    }
};
