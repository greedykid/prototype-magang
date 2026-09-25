<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lpks', function (Blueprint $table): void {
            $table->string('no_reg')->nullable()->unique()->after('id');
            $table->string('accreditation_number')->nullable()->index()->after('no_reg');
            $table->string('accreditation_type')->nullable()->after('name');
        });

        // Backfill data awal dari registration_number yang sudah ada
        $lpks = DB::table('lpks')->get();
        foreach ($lpks as $lpk) {
            $reg = trim((string) ($lpk->registration_number ?? ''));
            $isAccreditationFormat = preg_match('/^[A-Z]{2,4}-/i', $reg);
            $isNumeric = preg_match('/^\d+$/', $reg);

            $noReg = $isNumeric ? $reg : (string) ($lpk->id + 3000);
            $accreditationNo = $isAccreditationFormat ? $reg : ($reg ?: null);

            $type = 'Laboratorium Penguji';
            if (str_starts_with(strtoupper($reg), 'LK-')) {
                $type = 'Laboratorium Kalibrasi';
            } elseif (str_starts_with(strtoupper($reg), 'LM-')) {
                $type = 'Laboratorium Medik';
            } elseif (str_starts_with(strtoupper($reg), 'LI-')) {
                $type = 'Lembaga Inspeksi';
            }

            DB::table('lpks')->where('id', $lpk->id)->update([
                'no_reg' => $noReg,
                'accreditation_number' => $accreditationNo,
                'accreditation_type' => $type,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpks', function (Blueprint $table): void {
            $table->dropColumn(['no_reg', 'accreditation_number', 'accreditation_type']);
        });
    }
};
