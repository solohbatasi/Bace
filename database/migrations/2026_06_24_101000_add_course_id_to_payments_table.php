<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'course_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('course_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
                $table->index(['student_id', 'course_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'course_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('course_id');
            });
        }
    }
};
