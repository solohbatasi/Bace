<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'parent_course_id')) {
                $table->foreignId('parent_course_id')->nullable()->after('department_id')->constrained('courses')->nullOnDelete();
                $table->index('parent_course_id');
            }
        });

        foreach (['students', 'semester_registrations', 'enrollments', 'examinations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'subcourse_id')) {
                    $table->foreignId('subcourse_id')->nullable()->after('course_id')->constrained('courses')->nullOnDelete();
                    $table->index('subcourse_id');
                }
            });
        }

        DB::statement('DROP INDEX IF EXISTS semester_registrations_student_class_term_active_unique');
        DB::statement(<<<'SQL'
CREATE UNIQUE INDEX IF NOT EXISTS semester_registrations_student_course_subcourse_term_active_unique
ON semester_registrations (student_id, class_id, course_id, coalesce(subcourse_id, 0), semester_id, academic_year_id)
WHERE deleted_at IS NULL
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS semester_registrations_student_course_subcourse_term_active_unique');

        foreach (['examinations', 'enrollments', 'semester_registrations', 'students'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'subcourse_id')) {
                    $table->dropConstrainedForeignId('subcourse_id');
                }
            });
        }

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'parent_course_id')) {
                $table->dropConstrainedForeignId('parent_course_id');
            }
        });

        DB::statement(<<<'SQL'
CREATE UNIQUE INDEX IF NOT EXISTS semester_registrations_student_class_term_active_unique
ON semester_registrations (student_id, class_id, semester_id, academic_year_id)
WHERE deleted_at IS NULL
SQL);
    }
};
