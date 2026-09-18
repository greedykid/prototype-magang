<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function services(): View
    {
        return view('monitoring.services', ['services' => Service::withCount('checks')->latest()->get()]);
    }

    public function backups(Request $request): View
    {
        $system = $request->string('system')->trim()->toString();
        $status = $request->string('status')->toString();
        $recordedBy = $request->integer('recorded_by') ?: null;
        $finishedFrom = $request->date('finished_from')?->format('Y-m-d');
        $finishedTo = $request->date('finished_to')?->format('Y-m-d');
        $backups = Backup::query()->with('recorder')->when($system, fn ($query) => $query->where('system', 'like', "%{$system}%"))->when($status, fn ($query) => $query->where('status', $status))->when($recordedBy, fn ($query) => $query->where('recorded_by', $recordedBy))->when($finishedFrom, fn ($query) => $query->whereDate('finished_at', '>=', $finishedFrom))->when($finishedTo, fn ($query) => $query->whereDate('finished_at', '<=', $finishedTo))->latest()->paginate(12)->withQueryString();

        return view('monitoring.backups', array_merge(['backups' => $backups, 'systems' => Backup::query()->whereNotNull('system')->distinct()->orderBy('system')->pluck('system'), 'users' => User::query()->whereIn('id', Backup::query()->whereNotNull('recorded_by')->select('recorded_by'))->orderBy('name')->get(['id', 'name'])], compact('system', 'status', 'recordedBy', 'finishedFrom', 'finishedTo')));
    }
}
