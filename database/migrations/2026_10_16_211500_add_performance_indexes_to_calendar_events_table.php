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
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->index(['start_at', 'end_at'], 'calendar_events_start_end_at_index');
            $table->index(['created_by', 'start_at'], 'calendar_events_created_by_start_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex('calendar_events_start_end_at_index');
            $table->dropIndex('calendar_events_created_by_start_at_index');
        });
    }
};
