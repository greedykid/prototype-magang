<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $lpkId = $request->integer('lpk_id') ?: null;
        $assessmentType = $request->string('assessment_type')->toString();
        $status = $request->string('status')->toString();
        $tpFilter = $request->string('tp_status')->toString();
        $startFrom = $request->date('start_from')?->format('Y-m-d');
        $startTo = $request->date('start_to')?->format('Y-m-d');
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $assessments = Assessment::query()
            ->with(['lpk', 'expense'])
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($lpkId, fn ($query) => $query->where('lpk_id', $lpkId))
            ->when($assessmentType, function ($query) use ($assessmentType) {
                if ($assessmentType === 'Asesmen Awal') {
                    $query->whereIn('assessment_type', ['Asesmen Awal', 'INITIAL']);
                } elseif ($assessmentType === 'Surveilen') {
                    $query->whereIn('assessment_type', ['Surveilen', 'SURVEILLANCE']);
                } elseif ($assessmentType === 'Re-asesmen') {
                    $query->whereIn('assessment_type', ['Re-asesmen', 'REASSESSMENT']);
                } else {
                    $query->where('assessment_type', $assessmentType);
                }
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($tpFilter, function ($query) use ($tpFilter) {
                if ($tpFilter === 'OVERDUE') {
                    $query->tpOverdue();
                } elseif ($tpFilter === 'DUE_SOON') {
                    $query->tpDueSoon();
                } elseif ($tpFilter === 'ACTIVE') {
                    $query->tpActive();
                } else {
                    $query->where('tp_status', $tpFilter);
                }
            })
            ->when($startFrom, fn ($query) => $query->whereDate('start_at', '>=', $startFrom))
            ->when($startTo, fn ($query) => $query->whereDate('start_at', '<=', $startTo))
            ->orderBy('start_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('assessments.index', array_merge([
            'assessments' => $assessments,
            'lpks' => Lpk::orderBy('registration_number')->get(['id', 'registration_number', 'name']),
            'assessmentTypes' => Assessment::TYPES,
            'tpStatuses' => Assessment::TP_STATUSES,
        ], compact('search', 'lpkId', 'assessmentType', 'status', 'tpFilter', 'startFrom', 'startTo', 'perPage')));
    }

    public function create(Request $request): View
    {
        $assessment = new Assessment;

        if ($request->filled('lpk_id')) {
            $lpk = Lpk::find($request->input('lpk_id'));
            if ($lpk) {
                $assessment->lpk_id = $lpk->id;
                $assessment->location = $lpk->address ?: '';

                $alertCode = strtoupper((string) $request->input('alert_code', ''));
                $targetDateStr = $request->input('target_date');

                if ($alertCode === 'S1') {
                    $assessment->assessment_type = 'Surveilen';
                    $assessment->title = 'Surveilen 1 Siklus KAN - ' . $lpk->name;
                } elseif ($alertCode === 'S2') {
                    $assessment->assessment_type = 'Surveilen';
                    $assessment->title = 'Surveilen 2 Siklus KAN - ' . $lpk->name;
                } elseif ($alertCode === 'RA') {
                    $assessment->assessment_type = 'Re-asesmen';
                    $assessment->title = 'Re-asesmen Siklus KAN - ' . $lpk->name;
                } elseif ($request->filled('assessment_type')) {
                    $assessment->assessment_type = $request->input('assessment_type');
                    $assessment->title = ($assessment->assessment_type ?: 'Asesmen') . ' - ' . $lpk->name;
                } else {
                    $assessment->assessment_type = 'Surveilen';
                    $assessment->title = 'Asesmen Surveilen KAN - ' . $lpk->name;
                }

                if ($targetDateStr) {
                    try {
                        $targetDate = Carbon::parse($targetDateStr);
                        $assessment->start_at = $targetDate->copy()->setTime(9, 0);
                        $assessment->end_at = $targetDate->copy()->addDays(2)->setTime(17, 0);
                    } catch (\Throwable $e) {
                        // Abaikan error parsing tanggal
                    }
                } else {
                    $defaultStart = now()->addDays(14)->setTime(9, 0);
                    $assessment->start_at = $defaultStart;
                    $assessment->end_at = $defaultStart->copy()->addDays(2)->setTime(17, 0);
                }

                $assessment->status = 'PLANNED';
            }
        }

        if ($request->filled('title')) {
            $assessment->title = $request->input('title');
        }
        if ($request->filled('assessment_type')) {
            $assessment->assessment_type = $request->input('assessment_type');
        }
        if ($request->filled('location')) {
            $assessment->location = $request->input('location');
        }
        if ($request->filled('start_at')) {
            try {
                $assessment->start_at = Carbon::parse($request->input('start_at'));
            } catch (\Throwable $e) {}
        }
        if ($request->filled('end_at')) {
            try {
                $assessment->end_at = Carbon::parse($request->input('end_at'));
            } catch (\Throwable $e) {}
        }
        if ($request->filled('status')) {
            $assessment->status = $request->input('status');
        }

        return view('assessments.form', ['assessment' => $assessment, 'lpks' => Lpk::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['tp_has_extension'] = $request->boolean('tp_has_extension');
        if ($data['tp_has_extension'] && empty($data['tp_extension_months'])) {
            $data['tp_extension_months'] = 1;
        }

        $assessment = Assessment::create($data + ['created_by' => $request->user()->id]);

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
        $data = $this->validated($request);
        $data['tp_has_extension'] = $request->boolean('tp_has_extension');
        if ($data['tp_has_extension'] && empty($data['tp_extension_months'])) {
            $data['tp_extension_months'] = 1;
        }

        $assessment->update($data);

        return back()->with('success', 'Program asesmen berhasil diperbarui.');
    }

    public function updateTp(Request $request, Assessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'tp_status' => ['required', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'tp_due_date' => ['nullable', 'date'],
            'tp_has_extension' => ['nullable', 'boolean'],
            'tp_extension_months' => ['nullable', 'integer', 'min:0', 'max:1'],
            'tp_extension_letter_no' => ['nullable', 'string', 'max:255'],
            'tp_extension_date' => ['nullable', 'date'],
            'tp_extension_notes' => ['nullable', 'string'],
            'tp_satisfied_at' => ['nullable', 'date'],
            'tp_notes' => ['nullable', 'string'],
            'sk_number' => ['nullable', 'string', 'max:150'],
            'sk_date' => ['nullable', 'date'],
        ]);

        $validated['tp_has_extension'] = $request->boolean('tp_has_extension');
        if ($validated['tp_has_extension'] && empty($validated['tp_extension_months'])) {
            $validated['tp_extension_months'] = 1;
        }

        $assessment->update($validated);

        return back()->with('success', 'Status Tindakan Perbaikan (TP & VTP) berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'lpk_id' => ['required', 'exists:lpks,id'],
            'title' => ['required', 'string', 'max:255'],
            'assessment_type' => ['required', 'string', 'max:80'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED'],
            'lead_assessor' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'tp_status' => ['nullable', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'tp_due_date' => ['nullable', 'date'],
            'tp_has_extension' => ['nullable', 'boolean'],
            'tp_extension_months' => ['nullable', 'integer', 'min:0', 'max:1'],
            'tp_extension_letter_no' => ['nullable', 'string', 'max:255'],
            'tp_extension_date' => ['nullable', 'date'],
            'tp_extension_notes' => ['nullable', 'string'],
            'tp_satisfied_at' => ['nullable', 'date'],
            'tp_notes' => ['nullable', 'string'],
            'sk_number' => ['nullable', 'string', 'max:150'],
            'sk_date' => ['nullable', 'date'],
        ]);
    }
}
