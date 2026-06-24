<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semester_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('semester_registrations', 'course_fee')) {
                $table->decimal('course_fee', 12, 2)->nullable()->after('class_id');
            }
        });

        DB::statement('ALTER TABLE semester_registrations DROP CONSTRAINT IF EXISTS semester_registrations_course_fee_check');
        DB::statement('ALTER TABLE semester_registrations ADD CONSTRAINT semester_registrations_course_fee_check CHECK (course_fee IS NULL OR course_fee >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE semester_registrations DROP CONSTRAINT IF EXISTS semester_registrations_course_fee_check');

        Schema::table('semester_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('semester_registrations', 'course_fee')) {
                $table->dropColumn('course_fee');
            }
        });
    }
};
