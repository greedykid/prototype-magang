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
            $table->text('drive_url')->nullable()->after('expired_at');
            $table->dropColumn(['certificate_drive_url', 'amendment_drive_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpks', function (Blueprint $table): void {
            $table->dropColumn('drive_url');
            $table->text('certificate_drive_url')->nullable()->after('expired_at');
            $table->text('amendment_drive_url')->nullable()->after('certificate_drive_url');
        });
    }
};
