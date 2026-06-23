<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'fees')) {
                $table->decimal('fees', 12, 2)->default(0)->after('duration_semesters');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_fees_check");
            DB::statement("ALTER TABLE courses ADD CONSTRAINT courses_fees_check CHECK (fees >= 0)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_fees_check");
        }

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'fees')) {
                $table->dropColumn('fees');
            }
        });
    }
};
