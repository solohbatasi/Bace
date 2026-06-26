<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semester_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('semester_registrations', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('class_id')->constrained()->nullOnDelete();
                $table->index(['student_id', 'course_id', 'academic_year_id', 'semester_id'], 'semester_reg_course_term_index');
            }
        });

        DB::statement(<<<'SQL'
UPDATE semester_registrations
SET course_id = classes.course_id
FROM classes
WHERE semester_registrations.class_id = classes.id
  AND semester_registrations.course_id IS NULL
SQL);
    }

    public function down(): void
    {
        Schema::table('semester_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('semester_registrations', 'course_id')) {
                $table->dropIndex('semester_reg_course_term_index');
                $table->dropConstrainedForeignId('course_id');
            }
        });
    }
};
