<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lesson_tickets', 'verification_token')) {
            Schema::table('lesson_tickets', function (Blueprint $table) {
                $table->string('verification_token', 80)->nullable()->after('ticket_number')->unique();
            });
        }

        DB::table('lesson_tickets')
            ->whereNull('verification_token')
            ->orderBy('id')
            ->each(function ($ticket): void {
                do {
                    $token = Str::upper(Str::random(32));
                } while (DB::table('lesson_tickets')->where('verification_token', $token)->exists());

                DB::table('lesson_tickets')
                    ->where('id', $ticket->id)
                    ->update(['verification_token' => $token]);
            });

    }

    public function down(): void
    {
        if (Schema::hasColumn('lesson_tickets', 'verification_token')) {
            Schema::table('lesson_tickets', function (Blueprint $table) {
                $table->dropColumn('verification_token');
            });
        }
    }
};
