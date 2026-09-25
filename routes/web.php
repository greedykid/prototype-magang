<?php

use App\Http\Controllers\AccreditationBillingController;
use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AccreditationSignatureController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentExpenseController;
use App\Http\Controllers\AssessmentImportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleSheetsReportController;
use App\Http\Controllers\LpkController;
use App\Http\Controllers\LpkImportController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
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
    // 1. Fitur bersama & operasional unit internal (Admin & PIC)
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events/create', [CalendarEventController::class, 'create'])->name('calendar.events.create');
    Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
    Route::get('/calendar/events/{event}', [CalendarEventController::class, 'show'])->name('calendar.events.show');
    Route::get('/calendar/events/{event}/edit', [CalendarEventController::class, 'edit'])->name('calendar.events.edit');
    Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');

    // Pengelolaan Lembaga Penilaian Kesesuaian (LPK)
    Route::get('/lpks', [LpkController::class, 'index'])->name('lpks.index');
    Route::get('/lpks/create', [LpkController::class, 'create'])->name('lpks.create');
    Route::post('/lpks', [LpkController::class, 'store'])->name('lpks.store');
    Route::get('/lpks/{lpk}', [LpkController::class, 'show'])->name('lpks.show')->whereNumber('lpk');
    Route::get('/lpks/{lpk}/edit', [LpkController::class, 'edit'])->name('lpks.edit')->whereNumber('lpk');
    Route::put('/lpks/{lpk}', [LpkController::class, 'update'])->name('lpks.update')->whereNumber('lpk');
    Route::delete('/lpks/{lpk}', [LpkController::class, 'destroy'])->name('lpks.destroy')->whereNumber('lpk');
    Route::post('/lpks/{lpk}/notes', [LpkController::class, 'updateNotes'])->name('lpks.notes.update')->whereNumber('lpk');

    // Pengelolaan & Penjadwalan Asesmen (Dapat diakses Admin & PIC)
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::get('/assessments/create', [AssessmentController::class, 'create'])->name('assessments.create');
    Route::post('/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
    Route::get('/assessments/import/template', [AssessmentImportController::class, 'downloadTemplate'])->name('assessments.import.template');
    Route::post('/assessments/import', [AssessmentImportController::class, 'import'])->name('assessments.import');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show')->whereNumber('assessment');
    Route::get('/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])->name('assessments.edit')->whereNumber('assessment');
    Route::put('/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update')->whereNumber('assessment');
    Route::post('/assessments/{assessment}/tp-tracking', [AssessmentController::class, 'updateTp'])->name('assessments.tp.update')->whereNumber('assessment');
    Route::post('/assessments/{assessment}/expenses', [AssessmentExpenseController::class, 'storeOrUpdate'])->name('assessments.expenses.store');

    // Ekspor CSV Terotentikasi & Integrasi Google Sheets
    Route::get('/reports/expenses/export', [GoogleSheetsReportController::class, 'exportExpenses'])->name('reports.expenses.export');
    Route::get('/reports/lpks/export', [GoogleSheetsReportController::class, 'exportLpks'])->name('reports.lpks.export');
    Route::get('/reports/assessments/export', [GoogleSheetsReportController::class, 'exportAssessments'])->name('reports.assessments.export');

    // Pengaturan Profil & Kata Sandi Pengguna
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // 2. Administrasi & Monitoring Sistem Khusus (Administrator Unit)
    Route::middleware('role:admin')->group(function (): void {
        // Manajemen Pengguna & Hak Akses PIC / Admin
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/monitoring/backups', [MonitoringController::class, 'backups'])->name('monitoring.backups');

        Route::get('/lpks/import/template', [LpkImportController::class, 'downloadTemplate'])->name('lpks.import.template');
        Route::post('/lpks/import', [LpkImportController::class, 'import'])->name('lpks.import');
        Route::post('/lpks/{lpk}/send-surveillance-reminder', [LpkController::class, 'sendSurveillanceReminder'])->name('lpks.surveillance.remind')->whereNumber('lpk');

        Route::get('/accreditations', [AccreditationController::class, 'index'])->name('accreditations.index');
        Route::get('/accreditations/{accreditation}', [AccreditationController::class, 'show'])->name('accreditations.show');
        Route::post('/accreditations/{accreditation}/billings', [AccreditationBillingController::class, 'store'])->name('accreditations.billings.store');
        Route::post('/accreditations/{accreditation}/billings/{billing}/pay', [AccreditationBillingController::class, 'pay'])->name('accreditations.billings.pay');
        Route::post('/accreditations/{accreditation}/esign', [AccreditationSignatureController::class, 'sign'])->name('accreditations.esign.sign');

        // Verifikasi Biaya Perjalanan Dinas SBM Asesor (Wewenang Admin)
        Route::post('/assessments/{assessment}/expenses/verify', [AssessmentExpenseController::class, 'verify'])->name('assessments.expenses.verify');
    });
});
