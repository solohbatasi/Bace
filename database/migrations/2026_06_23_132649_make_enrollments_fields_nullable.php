<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->change();
            $table->unsignedBigInteger('class_id')->nullable()->change();
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->unsignedBigInteger('academic_year_id')->nullable()->change();
            $table->date('enrolled_on')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
            $table->unsignedBigInteger('class_id')->nullable(false)->change();
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
            $table->unsignedBigInteger('academic_year_id')->nullable(false)->change();
            $table->date('enrolled_on')->nullable(false)->change();
        });
    }
};
