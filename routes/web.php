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
use App\Http\Controllers\IssueController;
use App\Http\Controllers\LpkController;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('lpks', LpkController::class)->except(['destroy']);
    Route::get('/calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events/create', [CalendarEventController::class, 'create'])->name('calendar.events.create');
    Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
    Route::get('/calendar/events/{event}', [CalendarEventController::class, 'show'])->name('calendar.events.show');
    Route::get('/calendar/events/{event}/edit', [CalendarEventController::class, 'edit'])->name('calendar.events.edit');
    Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
    Route::get('/monitoring/services', [MonitoringController::class, 'services'])->name('monitoring.services');
    Route::get('/monitoring/backups', [MonitoringController::class, 'backups'])->name('monitoring.backups');
    Route::resource('amendments', AmendmentController::class)->except(['destroy']);
    Route::resource('assessments', AssessmentController::class)->except(['destroy']);
    Route::post('/assessments/{assessment}/expenses', [AssessmentExpenseController::class, 'storeOrUpdate'])->name('assessments.expenses.store');
    Route::post('/assessments/{assessment}/expenses/verify', [AssessmentExpenseController::class, 'verify'])->name('assessments.expenses.verify');

    Route::get('/accreditations', [AccreditationController::class, 'index'])->name('accreditations.index');
    Route::get('/accreditations/{accreditation}', [AccreditationController::class, 'show'])->name('accreditations.show');
    Route::post('/accreditations/{accreditation}/billings', [AccreditationBillingController::class, 'store'])->name('accreditations.billings.store');
    Route::post('/accreditations/{accreditation}/billings/{billing}/pay', [AccreditationBillingController::class, 'pay'])->name('accreditations.billings.pay');
    Route::post('/accreditations/{accreditation}/esign', [AccreditationSignatureController::class, 'sign'])->name('accreditations.esign.sign');

    Route::resource('issues', IssueController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('/issues/{issue}/follow-ups', [IssueController::class, 'storeFollowup'])->name('issues.followups.store');
});
