<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\Amendment;
use App\Models\Assessment;
use App\Models\Backup;
use App\Models\Issue;
use App\Models\IssueFollowup;
use App\Models\Lpk;
use App\Models\Service;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'lpkCount' => Lpk::count(),
            'activeAccreditationCount' => Accreditation::where('status', 'IN_PROGRESS')->count(),
            'accreditationStatusCounts' => Accreditation::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'openIssueCount' => Issue::whereIn('status', ['OPEN', 'IN_PROGRESS'])->count(),
            'overdueIssueCount' => Issue::whereIn('status', ['OPEN', 'IN_PROGRESS'])->whereDate('due_date', '<', now())->count(),
            'recentIssues' => Issue::with('lpk')->latest()->take(5)->get(),
            'recentFollowups' => IssueFollowup::with(['issue', 'user'])->latest()->take(4)->get(),
            'amendmentCount' => Amendment::whereNotIn('status', ['COMPLETED', 'REJECTED'])->count(),
            'assessmentCount' => Assessment::whereBetween('start_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'serviceIssueCount' => Service::whereIn('status', ['DEGRADED', 'DOWN'])->count(),
            'lastBackup' => Backup::latest('finished_at')->first(),
        ]);
    }
}
