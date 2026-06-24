<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'course_fee')) {
                $table->decimal('course_fee', 12, 2)->nullable()->after('course_id');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_course_fee_check');
            DB::statement('ALTER TABLE students ADD CONSTRAINT students_course_fee_check CHECK (course_fee IS NULL OR course_fee >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_course_fee_check');
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'course_fee')) {
                $table->dropColumn('course_fee');
            }
        });
    }
};
