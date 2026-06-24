<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('logo_path', 2048)->nullable();
            $table->string('official_email')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('marketing_email')->nullable();
            $table->string('primary_contact', 40)->nullable();
            $table->string('secondary_contact', 40)->nullable();
            $table->text('location')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->text('about')->nullable();
            $table->text('description')->nullable();
            $table->json('operation_hours')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_settings');
    }
};
