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
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('tp_status')->default('NONE')->after('notes');
            $table->date('tp_due_date')->nullable()->after('tp_status');
            $table->boolean('tp_has_extension')->default(false)->after('tp_due_date');
            $table->unsignedTinyInteger('tp_extension_months')->default(0)->after('tp_has_extension');
            $table->string('tp_extension_letter_no')->nullable()->after('tp_extension_months');
            $table->date('tp_extension_date')->nullable()->after('tp_extension_letter_no');
            $table->text('tp_extension_notes')->nullable()->after('tp_extension_date');
            $table->date('tp_satisfied_at')->nullable()->after('tp_extension_notes');
            $table->text('tp_notes')->nullable()->after('tp_satisfied_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn([
                'tp_status',
                'tp_due_date',
                'tp_has_extension',
                'tp_extension_months',
                'tp_extension_letter_no',
                'tp_extension_date',
                'tp_extension_notes',
                'tp_satisfied_at',
                'tp_notes',
            ]);
        });
    }
};
