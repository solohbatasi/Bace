<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            if (! Schema::hasColumn('examinations', 'can_edit_results')) {
                $table->boolean('can_edit_results')->default(true)->after('include_in_final_analysis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            if (Schema::hasColumn('examinations', 'can_edit_results')) {
                $table->dropColumn('can_edit_results');
            }
        });
    }
};
