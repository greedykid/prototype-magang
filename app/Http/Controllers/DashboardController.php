<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\Assessment;
use App\Models\Backup;
use App\Models\Lpk;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isPic = $user && $user->isPic();

        $activeTpCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->tpActive()
            ->count();

        $overdueTpCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->tpOverdue()
            ->count();

        $dueSoonTpCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->tpDueSoon(14)
            ->count();

        $urgentTpAssessments = Assessment::with('lpk')
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->whereNotIn('tp_status', [Assessment::TP_STATUS_NONE, Assessment::TP_STATUS_SATISFIED])
            ->whereNotNull('tp_due_date')
            ->get()
            ->filter(fn (Assessment $a) => $a->is_tp_overdue || ($a->days_remaining_tp !== null && $a->days_remaining_tp <= 14))
            ->sortBy('effective_tp_due_date')
            ->values()
            ->take(5);

        $lpkCount = $isPic ? Lpk::accessibleBy($user)->count() : Lpk::count();

        $activeAccreditationCount = Accreditation::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->where('status', 'IN_PROGRESS')
            ->count();

        $accreditationStatusCounts = Accreditation::query()
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $assessmentCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->whereBetween('start_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $upcomingAssessments = Assessment::with('lpk')
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at', 'asc')
            ->take(5)
            ->get();

        $managedLpks = Lpk::with(['pic', 'assessments'])
            ->when($isPic, fn ($q) => $q->accessibleBy($user))
            ->orderByRaw('CASE WHEN expired_at IS NOT NULL AND expired_at <= ? THEN 0 ELSE 1 END', [now()->addMonths(6)])
            ->orderBy('name')
            ->take(5)
            ->get();

        return view('dashboard', [
            'lpkCount' => $lpkCount,
            'activeAccreditationCount' => $activeAccreditationCount,
            'accreditationStatusCounts' => $accreditationStatusCounts,
            'assessmentCount' => $assessmentCount,
            'upcomingAssessments' => $upcomingAssessments,
            'managedLpks' => $managedLpks,
            'lastBackup' => Backup::latest('finished_at')->first(),
            'activeTpCount' => $activeTpCount,
            'overdueTpCount' => $overdueTpCount,
            'dueSoonTpCount' => $dueSoonTpCount,
            'urgentTpAssessments' => $urgentTpAssessments,
        ]);
    }
}
