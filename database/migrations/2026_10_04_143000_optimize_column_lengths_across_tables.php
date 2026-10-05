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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 100)->change();
            $table->string('email', 100)->change();
            $table->string('role', 20)->default('admin')->change();
            $table->string('password', 100)->change();
        });

        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email', 100)->change();
            $table->string('token', 100)->change();
        });

        Schema::table('lpks', function (Blueprint $table): void {
            $table->string('registration_number', 50)->change();
            $table->string('no_reg', 30)->nullable()->change();
            $table->string('accreditation_number', 50)->nullable()->change();
            $table->string('accreditation_type', 50)->nullable()->change();
            $table->string('name', 150)->change();
            $table->string('email', 100)->nullable()->change();
            $table->string('phone', 30)->nullable()->change();
            $table->string('status', 25)->default('ACTIVE')->change();
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->string('title', 150)->change();
            $table->string('assessment_type', 30)->default('INITIAL')->change();
            $table->string('location', 150)->nullable()->change();
            $table->string('status', 25)->default('PLANNED')->change();
            $table->string('lead_assessor', 100)->nullable()->change();
            $table->string('tp_status', 25)->default('NONE')->change();
            $table->string('tp_extension_letter_no', 80)->nullable()->change();
            $table->string('sk_number', 80)->nullable()->change();
            $table->string('eha_status', 25)->nullable()->default('BELUM_EHA')->change();
        });

        Schema::table('accreditations', function (Blueprint $table): void {
            $table->string('status', 25)->default('NOT_STARTED')->change();
            $table->string('pic', 100)->nullable()->change();
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->string('title', 150)->change();
            $table->string('event_type', 30)->nullable()->change();
            $table->string('location', 150)->nullable()->change();
            $table->string('status', 25)->default('PLANNED')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->string('title', 255)->change();
            $table->string('event_type', 100)->nullable()->change();
            $table->string('location', 255)->nullable()->change();
            $table->string('status', 50)->default('PLANNED')->change();
        });

        Schema::table('accreditations', function (Blueprint $table): void {
            $table->string('status', 50)->default('NOT_STARTED')->change();
            $table->string('pic', 255)->nullable()->change();
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->string('title', 255)->change();
            $table->string('assessment_type', 50)->default('INITIAL')->change();
            $table->string('location', 255)->nullable()->change();
            $table->string('status', 50)->default('PLANNED')->change();
            $table->string('lead_assessor', 255)->nullable()->change();
            $table->string('tp_status', 50)->default('NONE')->change();
            $table->string('tp_extension_letter_no', 100)->nullable()->change();
            $table->string('sk_number', 100)->nullable()->change();
            $table->string('eha_status', 50)->nullable()->default('BELUM_EHA')->change();
        });

        Schema::table('lpks', function (Blueprint $table): void {
            $table->string('registration_number', 100)->change();
            $table->string('no_reg', 100)->nullable()->change();
            $table->string('accreditation_number', 100)->nullable()->change();
            $table->string('accreditation_type', 100)->nullable()->change();
            $table->string('name', 255)->change();
            $table->string('email', 255)->nullable()->change();
            $table->string('phone', 50)->nullable()->change();
            $table->string('status', 50)->default('ACTIVE')->change();
        });

        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email', 255)->change();
            $table->string('token', 255)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 255)->change();
            $table->string('email', 255)->change();
            $table->string('role', 50)->default('admin')->change();
            $table->string('password', 255)->change();
        });
    }
};
