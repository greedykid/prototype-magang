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
            $table->index('lpk_id', 'assessments_lpk_id_index');
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->index('lpk_id', 'calendar_events_lpk_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropIndex('assessments_lpk_id_index');
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex('calendar_events_lpk_id_index');
        });
    }
};
