<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('enrollments', 'semester_registration_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->foreignId('semester_registration_id')->nullable()->after('id');
            });
        }

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'enrollments_semester_registration_id_foreign'
    ) THEN
        ALTER TABLE enrollments
        ADD CONSTRAINT enrollments_semester_registration_id_foreign
        FOREIGN KEY (semester_registration_id)
        REFERENCES semester_registrations(id)
        ON DELETE SET NULL;
    END IF;
END
$$;
SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'enrollments_semester_registration_id_foreign'
    ) THEN
        ALTER TABLE enrollments DROP CONSTRAINT enrollments_semester_registration_id_foreign;
    END IF;
END
$$;
SQL);

        if (Schema::hasColumn('enrollments', 'semester_registration_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('semester_registration_id');
            });
        }
    }
};
