<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isPic = $user && $user->isPic();

        // Dasbor adalah ruang kerja operasional mandiri: seluruh metrik & peringatan PIC hanya memuat LPK primer
        $activeTpCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->tpActive()
            ->count();

        $overdueTpCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->tpOverdue()
            ->count();

        $dueSoonTpCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->tpDueSoon(14)
            ->count();

        $urgentTpQuery = Assessment::with('lpk')
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->whereNotIn('tp_status', [Assessment::TP_STATUS_NONE, Assessment::TP_STATUS_SATISFIED])
            ->where(function ($q) {
                $q->tpOverdue()
                    ->orWhere(fn ($sub) => $sub->tpDueSoon(14));
            })
            ->orderBy('tp_due_date', 'asc');

        $urgentTpTotalCount = (clone $urgentTpQuery)->count();
        $urgentTpAssessments = $urgentTpQuery->take(5)->get();

        $lpkCount = $isPic ? Lpk::primaryFor($user)->count() : Lpk::count();

        $assessmentCount = Assessment::when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->whereBetween('start_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $upcomingAssessments = Assessment::with('lpk')
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at', 'asc')
            ->take(5)
            ->get();

        $managedLpks = Lpk::with(['pic', 'assessments'])
            ->when($isPic, fn ($q) => $q->primaryFor($user))
            ->orderByRaw('CASE WHEN expired_at IS NOT NULL AND expired_at <= ? THEN 0 ELSE 1 END', [now()->addMonths(6)])
            ->orderBy('name')
            ->take(5)
            ->get();

        return view('dashboard', [
            'lpkCount' => $lpkCount,
            'assessmentCount' => $assessmentCount,
            'upcomingAssessments' => $upcomingAssessments,
            'managedLpks' => $managedLpks,
            'activeTpCount' => $activeTpCount,
            'overdueTpCount' => $overdueTpCount,
            'dueSoonTpCount' => $dueSoonTpCount,
            'urgentTpAssessments' => $urgentTpAssessments,
            'urgentTpTotalCount' => $urgentTpTotalCount,
        ]);
    }
}
