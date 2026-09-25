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
        Schema::create('accreditations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lpk_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('NOT_STARTED');
            $table->date('start_date')->nullable();
            $table->date('pantek_at')->nullable();
            $table->date('target_date')->nullable();
            $table->date('target_output_at')->nullable();
            $table->date('output_released_at')->nullable();
            $table->string('pic')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accreditations');
    }
};
