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
        Schema::table('assessments', function (Blueprint $table): void {
            $table->string('location', 255)->nullable()->change();
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->string('location', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->string('location', 150)->nullable()->change();
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->string('location', 150)->nullable()->change();
        });
    }
};
