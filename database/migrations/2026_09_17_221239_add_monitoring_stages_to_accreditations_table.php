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
        Schema::table('accreditations', function (Blueprint $table): void {
            $table->date('pantek_at')->nullable()->after('start_date');
            $table->date('target_output_at')->nullable()->after('target_date');
            $table->date('output_released_at')->nullable()->after('target_output_at');
            $table->string('pic')->nullable()->after('output_released_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accreditations', function (Blueprint $table): void {
            $table->dropColumn(['pantek_at', 'target_output_at', 'output_released_at', 'pic']);
        });
    }
};
