<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semester_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('semester_registrations', 'course_score')) {
                $table->decimal('course_score', 5, 2)->nullable()->after('status');
            }

            if (! Schema::hasColumn('semester_registrations', 'course_grade')) {
                $table->string('course_grade', 20)->nullable()->after('course_score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('semester_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('semester_registrations', 'course_grade')) {
                $table->dropColumn('course_grade');
            }

            if (Schema::hasColumn('semester_registrations', 'course_score')) {
                $table->dropColumn('course_score');
            }
        });
    }
};
