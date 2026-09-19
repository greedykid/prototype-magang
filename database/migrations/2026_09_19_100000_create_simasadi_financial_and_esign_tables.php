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
        Schema::create('assessment_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('transport_cost')->default(0);
            $table->unsignedBigInteger('accommodation_cost')->default(0);
            $table->unsignedBigInteger('daily_allowance')->default(0);
            $table->unsignedBigInteger('package_data_cost')->default(0);
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->string('receipt_note')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status')->default('BELUM_DILAPORKAN'); // BELUM_DILAPORKAN, MENUNGGU_VERIFIKASI, TERVERIFIKASI, PERLU_REVISI
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('accreditation_billings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('accreditation_id')->constrained()->cascadeOnDelete();
            $table->string('billing_code', 15)->unique();
            $table->string('tariff_name')->default('PNBP Jasa Akreditasi Laboratorium / Lembaga Sertifikasi');
            $table->unsignedBigInteger('amount')->default(7500000);
            $table->dateTime('issued_at');
            $table->dateTime('expired_at');
            $table->string('status')->default('UNPAID'); // UNPAID, PAID, EXPIRED
            $table->string('ntpn', 20)->nullable();
            $table->string('ntb', 24)->nullable();
            $table->string('payment_channel')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('accreditation_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('accreditation_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('sk_number')->default('SK.KAN.042/BSN/IX/2026');
            $table->string('signer_name')->default('Drs. Kukuh S. Achmad, M.Sc.');
            $table->string('signer_title')->default('Ketua Komite Akreditasi Nasional (KAN)');
            $table->string('signer_nip')->nullable()->default('196508121990031002');
            $table->boolean('is_signed')->default(false);
            $table->dateTime('signed_at')->nullable();
            $table->string('certificate_series')->nullable();
            $table->string('verify_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accreditation_signatures');
        Schema::dropIfExists('accreditation_billings');
        Schema::dropIfExists('assessment_expenses');
    }
};
