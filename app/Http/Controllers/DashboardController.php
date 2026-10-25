<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected const PANEL_ITEM_LIMIT = 5;

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isPic = $user && $user->isPic();

        // Dasbor adalah ruang kerja operasional mandiri: seluruh metrik & peringatan PIC hanya memuat LPK primer
        $baseAssessmentQuery = Assessment::query()
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)));

        // Agregasi seluruh metrik status asesmen dan TP dalam 1 kueri tunggal ke basis data
        $tpStats = DB::query()->select([
            'active' => (clone $baseAssessmentQuery)->tpActive()->selectRaw('count(*)'),
            'overdue' => (clone $baseAssessmentQuery)->tpOverdue()->selectRaw('count(*)'),
            'due_soon' => (clone $baseAssessmentQuery)->tpDueSoon(14)->selectRaw('count(*)'),
            'month' => (clone $baseAssessmentQuery)->whereBetween('start_at', [now()->startOfMonth(), now()->endOfMonth()])->selectRaw('count(*)'),
        ])->first();

        $activeTpCount = (int) ($tpStats->active ?? 0);
        $overdueTpCount = (int) ($tpStats->overdue ?? 0);
        $dueSoonTpCount = (int) ($tpStats->due_soon ?? 0);
        $assessmentCount = (int) ($tpStats->month ?? 0);

        $urgentTpTotalCount = $overdueTpCount + $dueSoonTpCount;
        $urgentTpAssessments = $urgentTpTotalCount > 0
            ? Assessment::with('lpk')
                ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
                ->whereNotIn('tp_status', [Assessment::TP_STATUS_NONE, Assessment::TP_STATUS_SATISFIED])
                ->where(function ($q) {
                    $q->tpOverdue()
                        ->orWhere(fn ($sub) => $sub->tpDueSoon(14));
                })
                ->orderByRaw('COALESCE(tp_extension_date, tp_due_date, end_at, start_at) ASC')
                ->take(self::PANEL_ITEM_LIMIT)
                ->get()
            : collect();

        $baseLpkQuery = Lpk::query()
            ->when($isPic, fn ($q) => $q->primaryFor($user));

        $lpkCount = (clone $baseLpkQuery)->count();
        $suspendedCount = (clone $baseLpkQuery)->where('status', 'SUSPENDED')->count();
        $revokedCount = (clone $baseLpkQuery)->whereIn('status', ['REVOKED', 'DICABUT', 'INACTIVE'])->count();

        $upcomingAssessments = Assessment::with('lpk')
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at', 'asc')
            ->take(self::PANEL_ITEM_LIMIT)
            ->get();

        $managedLpks = Lpk::with(['pic', 'assessments'])
            ->when($isPic, fn ($q) => $q->primaryFor($user))
            ->orderByRaw('CASE WHEN expired_at IS NOT NULL AND expired_at <= ? THEN 0 ELSE 1 END', [now()->addMonths(6)])
            ->orderBy('name')
            ->take(self::PANEL_ITEM_LIMIT)
            ->get();

        return view('dashboard', [
            'lpkCount' => $lpkCount,
            'suspendedCount' => $suspendedCount,
            'revokedCount' => $revokedCount,
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
