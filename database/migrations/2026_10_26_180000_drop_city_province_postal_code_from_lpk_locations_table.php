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
        Schema::table('lpk_locations', function (Blueprint $table): void {
            $colsToDrop = [];
            foreach (['city', 'province', 'postal_code'] as $col) {
                if (Schema::hasColumn('lpk_locations', $col)) {
                    $colsToDrop[] = $col;
                }
            }

            if (! empty($colsToDrop)) {
                if (in_array('city', $colsToDrop, true)) {
                    try {
                        $table->dropIndex(['city']);
                    } catch (\Throwable $e) {
                        // Indeks mungkin tidak ada atau telah dibersihkan
                    }
                }

                $table->dropColumn($colsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpk_locations', function (Blueprint $table): void {
            if (! Schema::hasColumn('lpk_locations', 'city')) {
                $table->string('city', 100)->nullable();
                $table->index('city');
            }
            if (! Schema::hasColumn('lpk_locations', 'province')) {
                $table->string('province', 100)->nullable();
            }
            if (! Schema::hasColumn('lpk_locations', 'postal_code')) {
                $table->string('postal_code', 20)->nullable();
            }
        });
    }
};
