<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
                $table->index(['student_id', 'course_id', 'academic_year_id', 'semester_id'], 'enrollments_student_course_term_index');
            }
        });

        DB::statement(<<<'SQL'
UPDATE enrollments
SET course_id = units.course_id
FROM units
WHERE enrollments.unit_id = units.id
  AND enrollments.course_id IS NULL
SQL);

        DB::statement(<<<'SQL'
UPDATE enrollments
SET course_id = semester_registrations.course_id
FROM semester_registrations
WHERE enrollments.semester_registration_id = semester_registrations.id
  AND enrollments.course_id IS NULL
SQL);
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'course_id')) {
                $table->dropIndex('enrollments_student_course_term_index');
                $table->dropConstrainedForeignId('course_id');
            }
        });
    }
};
