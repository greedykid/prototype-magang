<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
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

        // Sinkronisasi otomatis status asesmen yang melewati batas SLA KAN menjadi SUSPENDED
        Assessment::query()
            ->whereNotIn('status', ['SUSPENDED', 'CANCELLED', 'COMPLETED'])
            ->tpOverdue()
            ->update(['status' => 'SUSPENDED']);

        $assessments = Assessment::query()
            ->with(['lpk', 'expense'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sk_number', 'like', "%{$search}%")
                  ->orWhereHas('lpk', fn ($lpkQ) => $lpkQ
                      ->where('name', 'like', "%{$search}%")
                      ->orWhere('registration_number', 'like', "%{$search}%")
                  );
            }))
            ->when($lpkId, fn ($query) => $query->where('lpk_id', $lpkId))
            ->when($assessmentType, function ($query) use ($assessmentType) {
                if ($assessmentType === Assessment::TYPE_AKREDITASI_AWAL || $assessmentType === 'Asesmen Awal') {
                    $query->whereIn('assessment_type', [Assessment::TYPE_AKREDITASI_AWAL, 'Asesmen Awal', 'INITIAL', 'AA']);
                } elseif ($assessmentType === Assessment::TYPE_SURVEILEN_1) {
                    $query->where(function ($q) {
                        $q->whereIn('assessment_type', [Assessment::TYPE_SURVEILEN_1, 'S1'])
                            ->orWhere(function ($sq) {
                                $sq->whereIn('assessment_type', ['Surveilen', 'SURVEILLANCE'])
                                    ->where(function ($titleQ) {
                                        $titleQ->where('title', 'like', '%Surveilen 1%')
                                            ->orWhere('title', 'like', '%(S1)%')
                                            ->orWhere('title', 'like', '% S1 %');
                                    })
                                    ->where('title', 'not like', '%PRL%');
                            });
                    });
                } elseif ($assessmentType === Assessment::TYPE_SURVEILEN_1_PRL) {
                    $query->where(function ($q) {
                        $q->whereIn('assessment_type', [Assessment::TYPE_SURVEILEN_1_PRL, 'S1 + PRL'])
                            ->orWhere(function ($sq) {
                                $sq->where('title', 'like', '%Surveilen 1%')
                                    ->where('title', 'like', '%PRL%');
                            });
                    });
                } elseif ($assessmentType === Assessment::TYPE_SURVEILEN_2) {
                    $query->where(function ($q) {
                        $q->whereIn('assessment_type', [Assessment::TYPE_SURVEILEN_2, 'S2'])
                            ->orWhere(function ($sq) {
                                $sq->whereIn('assessment_type', ['Surveilen', 'SURVEILLANCE'])
                                    ->where(function ($titleQ) {
                                        $titleQ->where('title', 'like', '%Surveilen 2%')
                                            ->orWhere('title', 'like', '%(S2)%')
                                            ->orWhere('title', 'like', '% S2 %');
                                    })
                                    ->where('title', 'not like', '%PRL%');
                            });
                    });
                } elseif ($assessmentType === Assessment::TYPE_SURVEILEN_2_PRL) {
                    $query->where(function ($q) {
                        $q->whereIn('assessment_type', [Assessment::TYPE_SURVEILEN_2_PRL, 'S2 + PRL'])
                            ->orWhere(function ($sq) {
                                $sq->where('title', 'like', '%Surveilen 2%')
                                    ->where('title', 'like', '%PRL%');
                            });
                    });
                } elseif ($assessmentType === Assessment::TYPE_STT) {
                    $query->whereIn('assessment_type', [Assessment::TYPE_STT, 'STT', 'Surveilen Tidak Terjadwal']);
                } elseif ($assessmentType === Assessment::TYPE_PRL) {
                    $query->whereIn('assessment_type', [Assessment::TYPE_PRL, 'PRL', 'Perluasan Ruang Lingkup', 'Perluasan Lingkup']);
                } elseif ($assessmentType === Assessment::TYPE_RE_AKREDITASI || $assessmentType === 'Re-asesmen') {
                    $query->whereIn('assessment_type', [Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'RA', 'Re-asesmen', 'REASSESSMENT']);
                } elseif ($assessmentType === 'Surveilen') {
                    $query->where(function ($q) {
                        $q->whereIn('assessment_type', [Assessment::TYPE_SURVEILEN_1, Assessment::TYPE_SURVEILEN_2, 'Surveilen', 'SURVEILLANCE', 'S1', 'S2']);
                    });
                } else {
                    $query->where('assessment_type', $assessmentType);
                }
            })
            ->when($status, function ($query, $status) {
                if ($status === 'SUSPENDED') {
                    $query->where(function ($q) {
                        $q->where('status', 'SUSPENDED')
                            ->orWhere(fn ($sub) => $sub->tpOverdue());
                    });
                } elseif ($status === 'IN_PROGRESS') {
                    $query->where('status', 'IN_PROGRESS')
                        ->whereNotIn('id', Assessment::select('id')->tpOverdue());
                } else {
                    $query->where('status', $status);
                }
            })
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

        if ($request->ajax() && $request->hasHeader('X-Partial-Content')) {
            return view('assessments.partials.table-content', compact('assessments', 'search', 'lpkId', 'assessmentType', 'status', 'tpFilter', 'startFrom', 'startTo', 'perPage'));
        }

        return view('assessments.index', array_merge([
            'assessments' => $assessments,
            'lpks' => Lpk::orderBy('registration_number')->get(['id', 'registration_number', 'name']),
            'assessmentTypes' => Assessment::TYPES,
            'tpStatuses' => Assessment::TP_STATUSES,
        ], compact('search', 'lpkId', 'assessmentType', 'status', 'tpFilter', 'startFrom', 'startTo', 'perPage')));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $lpksQuery = Lpk::orderBy('name');
        if ($user && $user->isPic()) {
            $lpksQuery->where(function ($q) use ($user) {
                $q->where('pic_user_id', $user->id)
                    ->orWhere('pic_id', $user->id)
                    ->orWhereNull('pic_user_id');
            });
        }

        $assessment = new Assessment;

        if ($request->filled('lpk_id')) {
            $lpk = Lpk::find($request->input('lpk_id'));
            if ($lpk) {
                $assessment->lpk_id = $lpk->id;
                $assessment->location = $lpk->address ?: '';

                $alertCode = strtoupper((string) $request->input('alert_code', ''));
                $targetDateStr = $request->input('target_date');

                if ($alertCode === 'S1') {
                    $assessment->assessment_type = 'SURVEILLANCE';
                    $assessment->title = 'Surveilen 1 Siklus KAN - ' . $lpk->name;
                } elseif ($alertCode === 'S2') {
                    $assessment->assessment_type = 'SURVEILLANCE_2';
                    $assessment->title = 'Surveilen 2 Siklus KAN - ' . $lpk->name;
                } elseif ($alertCode === 'RA') {
                    $assessment->assessment_type = 'REASSESSMENT';
                    $assessment->title = 'Re-asesmen Siklus KAN - ' . $lpk->name;
                } elseif ($request->filled('assessment_type')) {
                    $assessment->assessment_type = $request->input('assessment_type');
                    $assessment->title = ($assessment->assessment_type ?: 'Asesmen') . ' - ' . $lpk->name;
                } else {
                    $assessment->assessment_type = 'SURVEILLANCE';
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

        return view('assessments.form', ['assessment' => $assessment, 'lpks' => $lpksQuery->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['tp_has_extension'] = $request->boolean('tp_has_extension');
        if ($data['tp_has_extension']) {
            if (empty($data['tp_status']) || $data['tp_status'] === Assessment::TP_STATUS_NONE) {
                throw ValidationException::withMessages([
                    'tp_has_extension' => 'Perpanjangan waktu tindakan perbaikan (+1 bulan) hanya dapat diajukan jika terdapat upaya perbaikan (status Penyusunan Perbaikan atau Verifikasi Tim Asesor), bukan Nihil / Tanpa Tindakan Perbaikan.',
                ]);
            }
            if (empty($data['tp_extension_months'])) {
                $data['tp_extension_months'] = 1;
            }
        }

        $assessment = Assessment::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('assessments.show', $assessment)->with('success', 'Program asesmen berhasil dicatat.');
    }

    public function show(Assessment $assessment): View
    {
        return view('assessments.show', ['assessment' => $assessment->load(['lpk', 'expense.verifier'])]);
    }

    public function edit(Assessment $assessment, Request $request): View
    {
        $user = $request->user();
        $lpksQuery = Lpk::orderBy('name');
        if ($user && $user->isPic()) {
            $lpksQuery->where(function ($q) use ($user) {
                $q->where('pic_id', $user->id)->orWhereNull('pic_id');
            });
        }

        return view('assessments.form', ['assessment' => $assessment, 'lpks' => $lpksQuery->get()]);
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $data = $this->validated($request, $assessment);
        $data['tp_has_extension'] = $request->boolean('tp_has_extension');
        if ($data['tp_has_extension']) {
            if (empty($data['tp_status']) || $data['tp_status'] === Assessment::TP_STATUS_NONE) {
                throw ValidationException::withMessages([
                    'tp_has_extension' => 'Perpanjangan waktu tindakan perbaikan (+1 bulan) hanya dapat diajukan jika terdapat upaya perbaikan (status Penyusunan Perbaikan atau Verifikasi Tim Asesor), bukan Nihil / Tanpa Tindakan Perbaikan.',
                ]);
            }
            if (empty($data['tp_extension_months'])) {
                $data['tp_extension_months'] = 1;
            }
        }

        $assessment->update($data);

        return back()->with('success', 'Program asesmen berhasil diperbarui.');
    }

    public function updateTp(Request $request, Assessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED,SUSPENDED'],
            'tp_status' => ['required', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'tp_due_date' => ['nullable', 'date'],
            'tp_has_extension' => ['nullable', 'boolean'],
            'tp_extension_months' => ['nullable', 'integer', 'min:0', 'max:1'],
            'tp_extension_letter_no' => ['nullable', 'string', 'max:255'],
            'tp_extension_date' => ['nullable', 'date'],
            'tp_extension_notes' => ['nullable', 'string'],
            'tp_satisfied_at' => ['nullable', 'date'],
            'tp_notes' => ['nullable', 'string'],
            'report_date' => ['nullable', 'date'],
            'eha_date' => ['nullable', 'date'],
            'eha_status' => ['nullable', 'string', 'in:BELUM_EHA,DIREKOMENDASIKAN,PERLU_VERIFIKASI,CATATAN_KHUSUS'],
            'eha_notes' => ['nullable', 'string'],
            'sk_number' => ['nullable', 'string', 'max:150'],
            'sk_date' => ['nullable', 'date'],
        ]);

        $validated['tp_has_extension'] = $request->boolean('tp_has_extension');
        if ($validated['tp_has_extension']) {
            if ($validated['tp_status'] === Assessment::TP_STATUS_NONE) {
                throw ValidationException::withMessages([
                    'tp_has_extension' => 'Perpanjangan waktu tindakan perbaikan (+1 bulan) hanya dapat diajukan jika terdapat upaya perbaikan (status Penyusunan Perbaikan atau Verifikasi Tim Asesor), bukan Nihil / Tanpa Tindakan Perbaikan.',
                ]);
            }
            if (empty($validated['tp_extension_months'])) {
                $validated['tp_extension_months'] = 1;
            }
        }

        if (empty($validated['status'])) {
            $tpDueDate = ! empty($validated['tp_due_date']) ? Carbon::parse($validated['tp_due_date']) : $assessment->tp_due_date;
            $tpHasExtension = (bool) ($validated['tp_has_extension'] ?? $assessment->tp_has_extension);
            $tpExtensionMonths = (int) ($validated['tp_extension_months'] ?? $assessment->tp_extension_months);
            $tpSatisfiedAt = ! empty($validated['tp_satisfied_at']) ? Carbon::parse($validated['tp_satisfied_at']) : $assessment->tp_satisfied_at;

            $validated['status'] = Assessment::determineStatusFromDates(
                $assessment->start_at,
                $assessment->end_at,
                $assessment->status,
                $validated['tp_status'] ?? null,
                $validated['sk_number'] ?? null,
                ! empty($validated['report_date']) ? Carbon::parse($validated['report_date']) : $assessment->report_date,
                ! empty($validated['eha_date']) ? Carbon::parse($validated['eha_date']) : $assessment->eha_date,
                $tpDueDate,
                $tpHasExtension,
                $tpExtensionMonths,
                $tpSatisfiedAt,
                $assessment->assessment_type
            );
        }

        $assessment->update($validated);

        return back()->with('success', 'Status Tindakan Perbaikan (TP & VTP) serta milestone asesmen berhasil diperbarui.');
    }

    private function validated(Request $request, ?Assessment $assessment = null): array
    {
        $data = $request->validate([
            'lpk_id' => ['required', 'exists:lpks,id'],
            'title' => ['required', 'string', 'max:255'],
            'assessment_type' => ['required', 'string', 'max:80'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED,SUSPENDED'],
            'lead_assessor' => ['nullable', 'string', 'max:1000'],
            'assessment_team' => ['nullable', 'string', 'max:1000'],
            'report_date' => ['nullable', 'date'],
            'eha_date' => ['nullable', 'date'],
            'eha_status' => ['nullable', 'string', 'in:BELUM_EHA,DIREKOMENDASIKAN,PERLU_VERIFIKASI,CATATAN_KHUSUS'],
            'eha_notes' => ['nullable', 'string'],
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

        if (! empty($data['assessment_team'])) {
            $data['lead_assessor'] = $data['assessment_team'];
        } elseif (! empty($data['lead_assessor'])) {
            $data['assessment_team'] = $data['lead_assessor'];
        }

        $startAt = ! empty($data['start_at']) ? Carbon::parse($data['start_at']) : null;
        $endAt = ! empty($data['end_at']) ? Carbon::parse($data['end_at']) : null;
        $tpDueDate = ! empty($data['tp_due_date']) ? Carbon::parse($data['tp_due_date']) : $assessment?->tp_due_date;
        $tpHasExtension = (bool) ($data['tp_has_extension'] ?? $assessment?->tp_has_extension ?? false);
        $tpExtensionMonths = (int) ($data['tp_extension_months'] ?? $assessment?->tp_extension_months ?? 0);
        $tpSatisfiedAt = ! empty($data['tp_satisfied_at']) ? Carbon::parse($data['tp_satisfied_at']) : $assessment?->tp_satisfied_at;

        $data['status'] = Assessment::determineStatusFromDates(
            $startAt,
            $endAt,
            $assessment?->status ?? ($data['status'] ?? null),
            $data['tp_status'] ?? $assessment?->tp_status,
            $data['sk_number'] ?? $assessment?->sk_number,
            ! empty($data['report_date']) ? Carbon::parse($data['report_date']) : $assessment?->report_date,
            ! empty($data['eha_date']) ? Carbon::parse($data['eha_date']) : $assessment?->eha_date,
            $tpDueDate,
            $tpHasExtension,
            $tpExtensionMonths,
            $tpSatisfiedAt,
            $data['assessment_type'] ?? $assessment?->assessment_type
        );

        return $data;
    }
}
