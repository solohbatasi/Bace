<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('grade', 20)->nullable();
            $table->string('comment')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['course_id', 'sort_order']);
            $table->index(['unit_id', 'sort_order']);
        });

        DB::statement('ALTER TABLE score_levels ADD CONSTRAINT score_levels_owner_check CHECK ((course_id IS NOT NULL AND unit_id IS NULL) OR (course_id IS NULL AND unit_id IS NOT NULL))');
        DB::statement('ALTER TABLE score_levels ADD CONSTRAINT score_levels_range_check CHECK (min_score >= 0 AND max_score <= 100 AND min_score <= max_score)');
    }

    public function down(): void
    {
        Schema::dropIfExists('score_levels');
    }
};
