<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            if (! Schema::hasColumn('examinations', 'grading_mode')) {
                $table->string('grading_mode', 40)->default('score_levels_with_grades')->after('weight_percent');
            }
        });

        DB::statement('ALTER TABLE examinations DROP CONSTRAINT IF EXISTS examinations_grading_mode_check');
        DB::statement("ALTER TABLE examinations ADD CONSTRAINT examinations_grading_mode_check CHECK (grading_mode IN ('grade_only', 'score_levels', 'score_levels_with_grades'))");

        Schema::table('score_levels', function (Blueprint $table) {
            if (! Schema::hasColumn('score_levels', 'examination_id')) {
                $table->foreignId('examination_id')->nullable()->after('unit_id')->constrained()->cascadeOnDelete();
                $table->index(['examination_id', 'sort_order']);
            }
        });

        DB::statement('ALTER TABLE score_levels DROP CONSTRAINT IF EXISTS score_levels_owner_check');
        DB::statement('ALTER TABLE score_levels ADD CONSTRAINT score_levels_owner_check CHECK ((course_id IS NOT NULL AND unit_id IS NULL AND examination_id IS NULL) OR (course_id IS NULL AND unit_id IS NOT NULL AND examination_id IS NULL) OR (course_id IS NULL AND unit_id IS NULL AND examination_id IS NOT NULL))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE score_levels DROP CONSTRAINT IF EXISTS score_levels_owner_check');
        DB::statement('ALTER TABLE score_levels ADD CONSTRAINT score_levels_owner_check CHECK ((course_id IS NOT NULL AND unit_id IS NULL) OR (course_id IS NULL AND unit_id IS NOT NULL))');

        Schema::table('score_levels', function (Blueprint $table) {
            if (Schema::hasColumn('score_levels', 'examination_id')) {
                $table->dropIndex(['examination_id', 'sort_order']);
                $table->dropConstrainedForeignId('examination_id');
            }
        });

        DB::statement('ALTER TABLE examinations DROP CONSTRAINT IF EXISTS examinations_grading_mode_check');

        Schema::table('examinations', function (Blueprint $table) {
            if (Schema::hasColumn('examinations', 'grading_mode')) {
                $table->dropColumn('grading_mode');
            }
        });
    }
};
