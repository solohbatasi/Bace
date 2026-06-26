<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('score_levels')) {
            return;
        }

        DB::statement('ALTER TABLE score_levels DROP CONSTRAINT IF EXISTS score_levels_range_check');
        DB::statement('ALTER TABLE score_levels ALTER COLUMN min_score DROP NOT NULL');
        DB::statement('ALTER TABLE score_levels ALTER COLUMN max_score DROP NOT NULL');
        DB::statement("ALTER TABLE score_levels ADD CONSTRAINT score_levels_range_check CHECK ((min_score IS NULL AND max_score IS NULL) OR (min_score IS NOT NULL AND max_score IS NOT NULL AND min_score >= 0 AND max_score <= 100 AND min_score <= max_score))");
    }

    public function down(): void
    {
        if (! Schema::hasTable('score_levels')) {
            return;
        }

        DB::statement('ALTER TABLE score_levels DROP CONSTRAINT IF EXISTS score_levels_range_check');
        DB::statement('ALTER TABLE score_levels ALTER COLUMN min_score SET NOT NULL');
        DB::statement('ALTER TABLE score_levels ALTER COLUMN max_score SET NOT NULL');
        DB::statement('ALTER TABLE score_levels ADD CONSTRAINT score_levels_range_check CHECK (min_score >= 0 AND max_score <= 100 AND min_score <= max_score)');
    }
};
