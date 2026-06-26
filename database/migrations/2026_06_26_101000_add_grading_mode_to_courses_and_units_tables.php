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
            if (! Schema::hasColumn('courses', 'grading_mode')) {
                $table->string('grading_mode', 40)->default('score_levels_with_grades')->after('has_units');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'grading_mode')) {
                $table->string('grading_mode', 40)->default('score_levels_with_grades')->after('duration');
            }
        });

        DB::statement("ALTER TABLE courses ADD CONSTRAINT courses_grading_mode_check CHECK (grading_mode IN ('grade_only', 'score_levels', 'score_levels_with_grades'))");
        DB::statement("ALTER TABLE units ADD CONSTRAINT units_grading_mode_check CHECK (grading_mode IN ('grade_only', 'score_levels', 'score_levels_with_grades'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_grading_mode_check');
        DB::statement('ALTER TABLE units DROP CONSTRAINT IF EXISTS units_grading_mode_check');

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'grading_mode')) {
                $table->dropColumn('grading_mode');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'grading_mode')) {
                $table->dropColumn('grading_mode');
            }
        });
    }
};
