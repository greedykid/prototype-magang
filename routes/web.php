<?php

use App\Http\Controllers\AccreditationBillingController;
use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AccreditationSignatureController;
use App\Http\Controllers\AmendmentController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentExpenseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleSheetsReportController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\LpkController;
use App\Http\Controllers\LpkImportController;
use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

// Public verification for digital signature (BSrE QR Code simulation)
Route::get('/verify-sk/{hash}', [AccreditationSignatureController::class, 'verifyPublic'])->name('accreditations.esign.verify');

// Public live CSV feeds for Google Sheets =IMPORTDATA formula (protected by ?key= query)
Route::get('/feeds/expenses.csv', [GoogleSheetsReportController::class, 'feedExpenses'])->name('feeds.expenses');
Route::get('/feeds/lpks.csv', [GoogleSheetsReportController::class, 'feedLpks'])->name('feeds.lpks');
Route::get('/feeds/assessments.csv', [GoogleSheetsReportController::class, 'feedAssessments'])->name('feeds.assessments');

Route::middleware('auth')->group(function (): void {
    // 1. Fitur bersama (Admin, Staf, Asesor)
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events/create', [CalendarEventController::class, 'create'])->name('calendar.events.create');
    Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
    Route::get('/calendar/events/{event}', [CalendarEventController::class, 'show'])->name('calendar.events.show');
    Route::get('/calendar/events/{event}/edit', [CalendarEventController::class, 'edit'])->name('calendar.events.edit');
    Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');

    Route::resource('issues', IssueController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('/issues/{issue}/follow-ups', [IssueController::class, 'storeFollowup'])->name('issues.followups.store');

    Route::get('/lpks', [LpkController::class, 'index'])->name('lpks.index');
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show')->whereNumber('assessment');
    Route::post('/assessments/{assessment}/expenses', [AssessmentExpenseController::class, 'storeOrUpdate'])->name('assessments.expenses.store');

    // Ekspor CSV Terotentikasi & Integrasi Google Sheets
    Route::get('/reports/expenses/export', [GoogleSheetsReportController::class, 'exportExpenses'])->name('reports.expenses.export');
    Route::get('/reports/lpks/export', [GoogleSheetsReportController::class, 'exportLpks'])->name('reports.lpks.export');
    Route::get('/reports/assessments/export', [GoogleSheetsReportController::class, 'exportAssessments'])->name('reports.assessments.export');

    // 2. Monitoring Sistem & Infrastruktur (Hanya Administrator Sistem)
    Route::middleware('role:admin')->group(function (): void {
        Route::get('/monitoring/services', [MonitoringController::class, 'services'])->name('monitoring.services');
        Route::get('/monitoring/backups', [MonitoringController::class, 'backups'])->name('monitoring.backups');
    });

    // 3. Operasional Administrasi & Finansial (Administrator Sistem & Staf Administrasi)
    Route::middleware('role:admin,staf')->group(function (): void {
        Route::get('/lpks/create', [LpkController::class, 'create'])->name('lpks.create');
        Route::post('/lpks', [LpkController::class, 'store'])->name('lpks.store');
        Route::get('/lpks/import/template', [LpkImportController::class, 'downloadTemplate'])->name('lpks.import.template');
        Route::post('/lpks/import', [LpkImportController::class, 'import'])->name('lpks.import');
        Route::get('/lpks/{lpk}/edit', [LpkController::class, 'edit'])->name('lpks.edit')->whereNumber('lpk');
        Route::put('/lpks/{lpk}', [LpkController::class, 'update'])->name('lpks.update')->whereNumber('lpk');
        Route::delete('/lpks/{lpk}', [LpkController::class, 'destroy'])->name('lpks.destroy')->whereNumber('lpk');

        Route::resource('amendments', AmendmentController::class)->except(['destroy']);

        Route::get('/accreditations', [AccreditationController::class, 'index'])->name('accreditations.index');
        Route::get('/accreditations/{accreditation}', [AccreditationController::class, 'show'])->name('accreditations.show');
        Route::post('/accreditations/{accreditation}/billings', [AccreditationBillingController::class, 'store'])->name('accreditations.billings.store');
        Route::post('/accreditations/{accreditation}/billings/{billing}/pay', [AccreditationBillingController::class, 'pay'])->name('accreditations.billings.pay');
        Route::post('/accreditations/{accreditation}/esign', [AccreditationSignatureController::class, 'sign'])->name('accreditations.esign.sign');

        // Penjadwalan & Pengelolaan Asesmen (Wewenang Sekretariat KAN)
        Route::get('/assessments/create', [AssessmentController::class, 'create'])->name('assessments.create');
        Route::post('/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
        Route::get('/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])->name('assessments.edit')->whereNumber('assessment');
        Route::put('/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update')->whereNumber('assessment');

        // Verifikasi Biaya Perjalanan Dinas SBM Asesor
        Route::post('/assessments/{assessment}/expenses/verify', [AssessmentExpenseController::class, 'verify'])->name('assessments.expenses.verify');
    });

    // Detail LPK (ditempatkan setelah lpks/create agar tidak membentur wildcard)
    Route::get('/lpks/{lpk}', [LpkController::class, 'show'])->name('lpks.show')->whereNumber('lpk');
});
