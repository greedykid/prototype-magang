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
        Schema::table('lpks', function (Blueprint $table) {
            $table->date('certificate_date')->nullable()->after('scope');
            $table->timestamp('last_surveillance_notified_at')->nullable()->after('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpks', function (Blueprint $table) {
            $table->dropColumn(['certificate_date', 'last_surveillance_notified_at']);
        });
    }
};
