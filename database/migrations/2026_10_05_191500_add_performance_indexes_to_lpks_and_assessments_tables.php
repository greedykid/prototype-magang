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
        Schema::table('lpks', function (Blueprint $table): void {
            $table->index(['pic_id', 'status'], 'lpks_pic_id_status_index');
            $table->index(['status', 'expired_at'], 'lpks_status_expired_at_index');
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->index(['status', 'tp_status', 'tp_due_date'], 'assessments_status_tp_index');
            $table->index(['start_at', 'end_at'], 'assessments_start_end_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropIndex('assessments_status_tp_index');
            $table->dropIndex('assessments_start_end_at_index');
        });

        Schema::table('lpks', function (Blueprint $table): void {
            $table->dropIndex('lpks_pic_id_status_index');
            $table->dropIndex('lpks_status_expired_at_index');
        });
    }
};
