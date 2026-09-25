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
            $table->foreignId('pic_id')->nullable()->after('name')->constrained('users')->nullOnDelete();
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->text('assessment_team')->nullable()->after('lead_assessor');
            $table->date('report_date')->nullable()->after('end_at');
            $table->date('eha_date')->nullable()->after('report_date');
            $table->string('eha_status', 50)->nullable()->default('BELUM_EHA')->after('eha_date');
            $table->text('eha_notes')->nullable()->after('eha_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropColumn(['assessment_team', 'report_date', 'eha_date', 'eha_status', 'eha_notes']);
        });

        Schema::table('lpks', function (Blueprint $table): void {
            $table->dropForeign(['pic_id']);
            $table->dropColumn('pic_id');
        });
    }
};
