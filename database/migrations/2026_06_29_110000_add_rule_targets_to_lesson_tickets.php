<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_ticket_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_ticket_rules', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('course_id')->constrained()->restrictOnDelete();
            }

            if (! Schema::hasColumn('lesson_ticket_rules', 'target_type')) {
                $table->string('target_type', 30)->default('course')->after('unit_id');
            }
        });

        Schema::table('lesson_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_tickets', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('course_id')->constrained()->restrictOnDelete();
            }

            if (! Schema::hasColumn('lesson_tickets', 'target_type')) {
                $table->string('target_type', 30)->default('course')->after('unit_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_tickets', 'unit_id')) {
                $table->dropConstrainedForeignId('unit_id');
            }

            if (Schema::hasColumn('lesson_tickets', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });

        Schema::table('lesson_ticket_rules', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_ticket_rules', 'unit_id')) {
                $table->dropConstrainedForeignId('unit_id');
            }

            if (Schema::hasColumn('lesson_ticket_rules', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};
