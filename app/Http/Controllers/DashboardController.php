<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected const PANEL_ITEM_LIMIT = 10;

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

        // Ambil metrik ringkasan status LPK dari cache terisolasi per epoch
        $cacheKey = \App\Services\LpkSurveillanceService::getAlertsCacheKey($user);
        $dashboardCacheKey = 'simasadi_dash_counts_' . ($isPic ? 'pic_' . $user->id : 'admin') . '_' . $cacheKey;

        $cachedSummary = \Illuminate\Support\Facades\Cache::get($dashboardCacheKey);
        $allLpks = null;

        if ($cachedSummary) {
            $lpkCount = (int) $cachedSummary['lpkCount'];
            $activeCount = (int) $cachedSummary['activeCount'];
            $dueLpkCount = (int) $cachedSummary['dueLpkCount'];
            $suspendedCount = (int) $cachedSummary['suspendedCount'];
            $revokedCount = (int) $cachedSummary['revokedCount'];
        } else {
            $allLpks = (clone $baseLpkQuery)
                ->select(['id', 'name', 'registration_number', 'no_reg', 'accreditation_number', 'accreditation_type', 'status', 'certificate_date', 'expired_at', 'pic_id'])
                ->with([
                    'pic:id,name',
                    'assessments' => fn ($aq) => $aq->select([
                        'id', 'lpk_id', 'title', 'assessment_type', 'status', 'start_at', 'end_at',
                        'report_date', 'eha_date', 'eha_status', 'tp_status', 'tp_due_date',
                        'tp_has_extension', 'tp_extension_months', 'tp_satisfied_at', 'sk_number', 'sk_date'
                    ]),
                ])
                ->get();
            $lpkCount = $allLpks->count();

            $cachedAlerts = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if (empty($cachedAlerts)) {
                $cachedAlerts = [];
                foreach ($allLpks as $lpk) {
                    foreach ($lpk->getActiveSurveillanceAlerts() as $alert) {
                        $cachedAlerts[] = $alert;
                    }
                }
                $safeAlerts = array_map(function ($item) {
                    foreach (['target_date', 'notice_date', 'last_notified_at'] as $dateKey) {
                        if (! empty($item[$dateKey]) && $item[$dateKey] instanceof \Carbon\CarbonInterface) {
                            $item[$dateKey] = $item[$dateKey]->toIso8601String();
                        }
                    }
                    return $item;
                }, $cachedAlerts);
                \Illuminate\Support\Facades\Cache::put($cacheKey, $safeAlerts, now()->addHours(6));
            }
            $dueLpkIds = collect($cachedAlerts)->pluck('lpk_id')->unique();

            $activeCount = 0;
            $dueLpkCount = 0;
            $suspendedCount = 0;
            $revokedCount = 0;

            foreach ($allLpks as $lpk) {
                $dyn = $lpk->dynamic_status;
                $hasDue = $dueLpkIds->contains($lpk->id) || in_array($dyn, ['SURVEILLANCE_DUE', 'NEEDS_ACTION', 'JATUH_TEMPO']);
                $isSusp = ($dyn === 'SUSPENDED');
                $isRev = in_array($dyn, ['REVOKED', 'DICABUT', 'INACTIVE', 'EXPIRED', 'GRACE_PERIOD']);

                if ($hasDue) {
                    $dueLpkCount++;
                }
                if ($isSusp) {
                    $suspendedCount++;
                }
                if ($isRev) {
                    $revokedCount++;
                }
                if (! $hasDue && ! $isSusp && ! $isRev) {
                    $activeCount++;
                }
            }

            \Illuminate\Support\Facades\Cache::put($dashboardCacheKey, compact(
                'lpkCount',
                'activeCount',
                'dueLpkCount',
                'suspendedCount',
                'revokedCount'
            ), now()->addHours(6));
        }

        $upcomingAssessments = Assessment::with('lpk')
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at', 'asc')
            ->take(self::PANEL_ITEM_LIMIT)
            ->get();

        // Untuk PIC tampilkan seluruh laboratorium binaan primernya, untuk admin batasi query langsung
        if ($isPic) {
            $picLpks = $allLpks ?: (clone $baseLpkQuery)
                ->select(['id', 'name', 'registration_number', 'no_reg', 'accreditation_number', 'accreditation_type', 'status', 'certificate_date', 'expired_at', 'pic_id'])
                ->with(['pic:id,name', 'assessments'])
                ->get();
            $managedLpks = $picLpks
                ->sortBy([
                    fn ($a, $b) => ($a->expired_at && $a->expired_at <= now()->addMonths(6) ? 0 : 1) <=> ($b->expired_at && $b->expired_at <= now()->addMonths(6) ? 0 : 1),
                    fn ($a, $b) => strcasecmp($a->name, $b->name),
                ])
                ->values();
        } else {
            $managedLpks = (clone $baseLpkQuery)
                ->with(['pic', 'assessments'])
                ->orderByRaw('CASE WHEN expired_at IS NOT NULL AND expired_at <= ? THEN 0 ELSE 1 END ASC', [now()->addMonths(6)->toDateString()])
                ->orderBy('name', 'asc')
                ->take(self::PANEL_ITEM_LIMIT)
                ->get();
        }

        return view('dashboard', [
            'lpkCount' => $lpkCount,
            'activeCount' => $activeCount,
            'dueLpkCount' => $dueLpkCount,
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
