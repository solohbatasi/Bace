<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function ($table) {
            if (! Schema::hasColumn('courses', 'duration_type')) {
                $table->string('duration_type', 30)->default('semesters')->after('qualification_level');
            }
        });

        DB::statement("UPDATE courses SET duration_type = CASE WHEN duration IS NOT NULL AND duration <> '' THEN 'custom' ELSE 'semesters' END WHERE duration_type IS NULL OR duration_type = ''");
        DB::statement('ALTER TABLE courses ALTER COLUMN duration_semesters DROP NOT NULL');
        DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_duration_type_check');
        DB::statement("ALTER TABLE courses ADD CONSTRAINT courses_duration_type_check CHECK (duration_type IN ('semesters', 'custom'))");
        DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_duration_requirement_check');
        DB::statement("ALTER TABLE courses ADD CONSTRAINT courses_duration_requirement_check CHECK ((duration_type = 'semesters' AND duration_semesters IS NOT NULL AND duration_semesters > 0) OR (duration_type = 'custom' AND duration IS NOT NULL AND duration <> ''))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_duration_requirement_check');
        DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_duration_type_check');
        DB::statement('UPDATE courses SET duration_semesters = 1 WHERE duration_semesters IS NULL');
        DB::statement('ALTER TABLE courses ALTER COLUMN duration_semesters SET NOT NULL');

        Schema::table('courses', function ($table) {
            if (Schema::hasColumn('courses', 'duration_type')) {
                $table->dropColumn('duration_type');
            }
        });
    }
};
