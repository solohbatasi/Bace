<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('score_levels')) {
            DB::statement('ALTER TABLE score_levels ALTER COLUMN grade DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('score_levels')) {
            DB::statement('ALTER TABLE score_levels ALTER COLUMN grade SET NOT NULL');
        }
    }
};
