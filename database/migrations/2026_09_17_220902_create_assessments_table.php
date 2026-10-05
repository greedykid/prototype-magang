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
        Schema::create('assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lpk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title', 150);
            $table->string('assessment_type', 30)->default('INITIAL');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('location', 150)->nullable();
            $table->string('status', 25)->default('PLANNED');
            $table->string('lead_assessor', 100)->nullable();
            $table->text('notes')->nullable();

            // SLA Tindakan Perbaikan (TP & VTP) KAN
            $table->string('tp_status', 25)->default('NONE');
            $table->date('tp_due_date')->nullable();
            $table->boolean('tp_has_extension')->default(false);
            $table->unsignedTinyInteger('tp_extension_months')->default(0);
            $table->string('tp_extension_letter_no', 80)->nullable();
            $table->date('tp_extension_date')->nullable();
            $table->text('tp_extension_notes')->nullable();
            $table->date('tp_satisfied_at')->nullable();
            $table->text('tp_notes')->nullable();

            // Surat Keputusan (SK) KAN
            $table->string('sk_number', 80)->nullable();
            $table->date('sk_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
