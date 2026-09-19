<?php

namespace App\Http\Controllers;

use App\Models\Amendment;
use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmendmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $lpkId = $request->integer('lpk_id') ?: null;
        $amendmentType = $request->string('amendment_type')->toString();
        $status = $request->string('status')->toString();
        $submittedFrom = $request->date('submitted_from')?->format('Y-m-d');
        $submittedTo = $request->date('submitted_to')?->format('Y-m-d');
        $targetFrom = $request->date('target_from')?->format('Y-m-d');
        $targetTo = $request->date('target_to')?->format('Y-m-d');
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $amendments = Amendment::query()->with('lpk')->when($search, fn ($query) => $query->where('submission_number', 'like', "%{$search}%"))->when($lpkId, fn ($query) => $query->where('lpk_id', $lpkId))->when($amendmentType, fn ($query) => $query->where('amendment_type', $amendmentType))->when($status, fn ($query) => $query->where('status', $status))->when($submittedFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $submittedFrom))->when($submittedTo, fn ($query) => $query->whereDate('submitted_at', '<=', $submittedTo))->when($targetFrom, fn ($query) => $query->whereDate('target_date', '>=', $targetFrom))->when($targetTo, fn ($query) => $query->whereDate('target_date', '<=', $targetTo))->latest()->paginate($perPage)->withQueryString();

        return view('amendments.index', array_merge(['amendments' => $amendments, 'lpks' => Lpk::orderBy('name')->get(['id', 'name']), 'amendmentTypes' => Amendment::query()->whereNotNull('amendment_type')->distinct()->orderBy('amendment_type')->pluck('amendment_type')], compact('search', 'lpkId', 'amendmentType', 'status', 'submittedFrom', 'submittedTo', 'targetFrom', 'targetTo', 'perPage')));
    }

    public function create(): View
    {
        return view('amendments.form', ['amendment' => new Amendment, 'lpks' => Lpk::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $amendment = Amendment::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('amendments.show', $amendment)->with('success', 'Pengajuan amandemen berhasil dicatat.');
    }

    public function show(Amendment $amendment): View
    {
        return view('amendments.show', ['amendment' => $amendment->load('lpk')]);
    }

    public function edit(Amendment $amendment): View
    {
        return view('amendments.form', ['amendment' => $amendment, 'lpks' => Lpk::orderBy('name')->get()]);
    }

    public function update(Request $request, Amendment $amendment): RedirectResponse
    {
        $amendment->update($this->validated($request));

        return back()->with('success', 'Status amandemen berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['lpk_id' => ['required', 'exists:lpks,id'], 'submission_number' => ['required', 'string', 'max:80', 'unique:amendments,submission_number,'.($request->route('amendment')?->id ?? 'NULL')], 'amendment_type' => ['required', 'string', 'max:255'], 'submitted_at' => ['required', 'date'], 'status' => ['required', 'in:SUBMITTED,UNDER_REVIEW,NEED_REVISION,APPROVED,REJECTED,COMPLETED'], 'target_date' => ['nullable', 'date'], 'completed_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string']]);
    }
}
