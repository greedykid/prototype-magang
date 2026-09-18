<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IssueController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $lpkId = $request->integer('lpk_id') ?: null;
        $priority = $request->string('priority')->toString();
        $status = $request->string('status')->toString();
        $dueFrom = $request->date('due_from')?->format('Y-m-d');
        $dueTo = $request->date('due_to')?->format('Y-m-d');
        $issues = Issue::query()->with('lpk')->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))->when($lpkId, fn ($query) => $query->where('lpk_id', $lpkId))->when(in_array($priority, ['LOW', 'MEDIUM', 'HIGH'], true), fn ($query) => $query->where('priority', $priority))->when(in_array($status, ['OPEN', 'IN_PROGRESS', 'RESOLVED'], true), fn ($query) => $query->where('status', $status))->when($dueFrom, fn ($query) => $query->whereDate('due_date', '>=', $dueFrom))->when($dueTo, fn ($query) => $query->whereDate('due_date', '<=', $dueTo))->latest()->paginate(10)->withQueryString();

        return view('issues.index', array_merge(['issues' => $issues, 'lpks' => Lpk::orderBy('name')->get(['id', 'name'])], compact('search', 'lpkId', 'priority', 'status', 'dueFrom', 'dueTo')));
    }

    public function create(): View
    {
        return view('issues.form', ['lpks' => Lpk::orderBy('name')->get(), 'issue' => new Issue]);
    }

    public function store(Request $request): RedirectResponse
    {
        $issue = Issue::create($request->validate(['lpk_id' => ['required', 'exists:lpks,id'], 'title' => ['required', 'string', 'max:255'], 'description' => ['required', 'string'], 'priority' => ['required', 'in:LOW,MEDIUM,HIGH'], 'status' => ['required', 'in:OPEN,IN_PROGRESS,RESOLVED'], 'due_date' => ['nullable', 'date']]) + ['created_by' => $request->user()->id]);

        return redirect()->route('issues.show', $issue)->with('success', 'Laporan masalah berhasil dibuat.');
    }

    public function show(Issue $issue): View
    {
        return view('issues.show', ['issue' => $issue->load(['lpk', 'creator', 'followups.user'])]);
    }

    public function update(Request $request, Issue $issue): RedirectResponse
    {
        $issue->update($request->validate(['status' => ['required', 'in:OPEN,IN_PROGRESS,RESOLVED']]));

        return back()->with('success', 'Status masalah berhasil diperbarui.');
    }

    public function storeFollowup(Request $request, Issue $issue): RedirectResponse
    {
        $issue->followups()->create($request->validate(['note' => ['required', 'string', 'max:5000']]) + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Tindak lanjut berhasil ditambahkan.');
    }
}
