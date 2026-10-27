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
        Schema::create('lpk_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lpk_id')->constrained('lpks')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('location_type', 50)->default('BRANCH_LAB');
            $table->boolean('is_main')->default(false);
            $table->text('address')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->text('scope_notes')->nullable();
            $table->timestamps();

            $table->index(['lpk_id', 'is_main']);
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->foreignId('lpk_location_id')->nullable()->after('lpk_id')->constrained('lpk_locations')->nullOnDelete();
            $table->index(['lpk_location_id', 'start_at']);
        });

        // Auto-seed data lokasi default (Kantor Pusat) untuk LPK yang sudah ada
        $now = now();
        $existingLpks = DB::table('lpks')->select('id', 'name', 'address', 'phone', 'email')->get();
        $defaultLocations = [];

        foreach ($existingLpks as $lpk) {
            $defaultLocations[] = [
                'lpk_id' => $lpk->id,
                'name' => 'Kantor Pusat',
                'location_type' => 'HEAD_OFFICE',
                'is_main' => true,
                'address' => $lpk->address ?: 'Alamat belum diatur',
                'contact_person' => null,
                'contact_phone' => $lpk->phone,
                'contact_email' => $lpk->email,
                'scope_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($defaultLocations)) {
            // Insert in chunks of 50 to avoid potential query size limits
            foreach (array_chunk($defaultLocations, 50) as $chunk) {
                DB::table('lpk_locations')->insert($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropForeign(['lpk_location_id']);
            $table->dropIndex(['lpk_location_id', 'start_at']);
            $table->dropColumn('lpk_location_id');
        });

        Schema::dropIfExists('lpk_locations');
    }
};
