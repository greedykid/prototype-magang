<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $lpkId = $request->integer('lpk_id') ?: null;
        $assessmentType = $request->string('assessment_type')->toString();
        $status = $request->string('status')->toString();
        $startFrom = $request->date('start_from')?->format('Y-m-d');
        $startTo = $request->date('start_to')?->format('Y-m-d');
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $assessments = Assessment::query()->with(['lpk', 'expense'])->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))->when($lpkId, fn ($query) => $query->where('lpk_id', $lpkId))->when($assessmentType, fn ($query) => $query->where('assessment_type', $assessmentType))->when($status, fn ($query) => $query->where('status', $status))->when($startFrom, fn ($query) => $query->whereDate('start_at', '>=', $startFrom))->when($startTo, fn ($query) => $query->whereDate('start_at', '<=', $startTo))->orderBy('start_at')->paginate($perPage)->withQueryString();

        return view('assessments.index', array_merge(['assessments' => $assessments, 'lpks' => Lpk::orderBy('name')->get(['id', 'name']), 'assessmentTypes' => Assessment::query()->whereNotNull('assessment_type')->distinct()->orderBy('assessment_type')->pluck('assessment_type')], compact('search', 'lpkId', 'assessmentType', 'status', 'startFrom', 'startTo', 'perPage')));
    }

    public function create(): View
    {
        return view('assessments.form', ['assessment' => new Assessment, 'lpks' => Lpk::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $assessment = Assessment::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('assessments.show', $assessment)->with('success', 'Program asesmen berhasil dicatat.');
    }

    public function show(Assessment $assessment): View
    {
        return view('assessments.show', ['assessment' => $assessment->load(['lpk', 'expense.verifier'])]);
    }

    public function edit(Assessment $assessment): View
    {
        return view('assessments.form', ['assessment' => $assessment, 'lpks' => Lpk::orderBy('name')->get()]);
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $assessment->update($this->validated($request));

        return back()->with('success', 'Program asesmen berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['lpk_id' => ['required', 'exists:lpks,id'], 'title' => ['required', 'string', 'max:255'], 'assessment_type' => ['required', 'string', 'max:80'], 'start_at' => ['required', 'date'], 'end_at' => ['required', 'date', 'after:start_at'], 'location' => ['nullable', 'string', 'max:255'], 'status' => ['required', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED'], 'lead_assessor' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string']]);
    }
}
