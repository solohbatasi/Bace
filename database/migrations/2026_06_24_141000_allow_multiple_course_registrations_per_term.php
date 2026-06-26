<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS semester_registrations_student_term_active_unique');

        DB::statement(<<<'SQL'
CREATE UNIQUE INDEX IF NOT EXISTS semester_registrations_student_class_term_active_unique
ON semester_registrations (student_id, class_id, semester_id, academic_year_id)
WHERE deleted_at IS NULL
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS semester_registrations_student_class_term_active_unique');

        DB::statement(<<<'SQL'
CREATE UNIQUE INDEX IF NOT EXISTS semester_registrations_student_term_active_unique
ON semester_registrations (student_id, semester_id, academic_year_id)
WHERE deleted_at IS NULL
SQL);
    }
};
