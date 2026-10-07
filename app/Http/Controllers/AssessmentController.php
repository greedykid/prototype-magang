<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Http\Requests\UpdateAssessmentTpRequest;
use App\Models\Assessment;
use App\Models\Lpk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $user = $request->user();
        $isPic = $user && $user->isPic();

        $assessments = Assessment::query()
            ->with(['lpk'])
            ->when($isPic, fn ($query) => $query->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
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

        $linkedOwnersCount = ($user && $user->isPic()) ? $user->linkedOwners()->count() : 0;

        if ($request->ajax() && $request->hasHeader('X-Partial-Content')) {
            return view('assessments.partials.table-content', compact('assessments', 'search', 'lpkId', 'assessmentType', 'status', 'tpFilter', 'startFrom', 'startTo', 'perPage'));
        }

        return view('assessments.index', array_merge([
            'assessments' => $assessments,
            'lpks' => Lpk::primaryFor($user)->orderBy('registration_number')->get(['id', 'registration_number', 'name']),
            'assessmentTypes' => Assessment::TYPES,
            'tpStatuses' => Assessment::TP_STATUSES,
            'linkedOwnersCount' => $linkedOwnersCount,
        ], compact('search', 'lpkId', 'assessmentType', 'status', 'tpFilter', 'startFrom', 'startTo', 'perPage')));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $lpksQuery = Lpk::orderBy('name');
        if ($user && $user->isPic()) {
            $lpksQuery->where(function ($q) use ($user) {
                $q->where('pic_id', $user->id)
                    ->orWhereHas('members', fn ($mq) => $mq->where('lpk_members.user_id', $user->id)->where('lpk_members.role', 'lead'));
            });
        }

        $assessment = new Assessment;

        if ($request->filled('lpk_id')) {
            $lpk = Lpk::find($request->input('lpk_id'));
            if ($lpk) {
                if ($user && ! $lpk->canManage($user)) {
                    abort(403, 'Anda tidak memiliki hak untuk menambah asesmen pada LPK ini.');
                }

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
        if ($request->filled('start_date')) {
            try {
                $time = $request->input('start_time', '09:00');
                $assessment->start_at = Carbon::parse($request->input('start_date').' '.$time);
            } catch (\Throwable $e) {}
        } elseif ($request->filled('start_at')) {
            try {
                $assessment->start_at = Carbon::parse($request->input('start_at'));
            } catch (\Throwable $e) {}
        }

        if ($request->filled('end_date')) {
            try {
                $time = $request->input('end_time', '17:00');
                $assessment->end_at = Carbon::parse($request->input('end_date').' '.$time);
            } catch (\Throwable $e) {}
        } elseif ($request->filled('end_at')) {
            try {
                $assessment->end_at = Carbon::parse($request->input('end_at'));
            } catch (\Throwable $e) {}
        }

        if (! $assessment->start_at) {
            $defaultStart = now()->addDays(14)->setTime(9, 0);
            $assessment->start_at = $defaultStart;
            $assessment->end_at = $defaultStart->copy()->addDays(2)->setTime(17, 0);
        }

        if ($request->filled('status')) {
            $assessment->status = $request->input('status');
        }

        if ($request->filled('submission_due_date')) {
            try {
                $assessment->submission_due_date = Carbon::parse($request->input('submission_due_date'));
            } catch (\Throwable $e) {}
        }

        return view('assessments.form', ['assessment' => $assessment, 'lpks' => $lpksQuery->get()]);
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $data = $request->normalizedPayload();
        $data['tp_has_extension'] = $request->boolean('tp_has_extension') || ! empty($data['tp_extension_letter_no']);
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

        $user = $request->user();
        $targetLpk = Lpk::find($data['lpk_id']);
        if ($user && $targetLpk && ! $targetLpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk menambah asesmen pada LPK ini.');
        }

        $assessment = Assessment::create($data + ['created_by' => $user->id]);

        return redirect()->route('assessments.index')->with('success', 'Program asesmen berhasil dicatat.');
    }

    public function show(Assessment $assessment, Request $request): View
    {
        $user = $request->user();
        if ($user && $assessment->lpk && ! $assessment->lpk->canView($user)) {
            abort(403, 'Anda tidak memiliki hak untuk melihat program asesmen ini.');
        }

        return view('assessments.show', ['assessment' => $assessment->load(['lpk'])]);
    }

    public function edit(Assessment $assessment, Request $request): View
    {
        $user = $request->user();
        if ($user && $assessment->lpk && ! $assessment->lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah program asesmen ini.');
        }

        $lpksQuery = Lpk::orderBy('name');
        if ($user && $user->isPic()) {
            $lpksQuery->where(function ($q) use ($user) {
                $q->where('pic_id', $user->id)
                    ->orWhereHas('members', fn ($mq) => $mq->where('lpk_members.user_id', $user->id)->where('lpk_members.role', 'lead'));
            });
        }

        return view('assessments.form', ['assessment' => $assessment, 'lpks' => $lpksQuery->get()]);
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $user = $request->user();
        if ($user && $assessment->lpk && ! $assessment->lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah program asesmen ini.');
        }

        $data = $request->normalizedPayload($assessment);
        if (! empty($data['lpk_id']) && (int) $data['lpk_id'] !== (int) $assessment->lpk_id) {
            $newLpk = Lpk::find($data['lpk_id']);
            if ($user && $newLpk && ! $newLpk->canManage($user)) {
                abort(403, 'Anda tidak memiliki hak untuk memindahkan asesmen ke LPK ini.');
            }
        }

        $data['tp_has_extension'] = $request->boolean('tp_has_extension') || ! empty($data['tp_extension_letter_no']);
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

        return redirect()->route('assessments.index')->with('success', 'Program asesmen berhasil diperbarui.');
    }

    public function updateTp(UpdateAssessmentTpRequest $request, Assessment $assessment): RedirectResponse
    {
        $user = request()->user();
        if ($user && $assessment->lpk && ! $assessment->lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk memperbarui status tindakan perbaikan pada asesmen ini.');
        }

        $validated = $request->normalizedData($assessment);
        $assessment->update($validated);

        return back()->with('success', 'Status Tindakan Perbaikan (TP & VTP) serta milestone asesmen berhasil diperbarui.');
    }

    public function destroy(Assessment $assessment, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user && $assessment->lpk && ! $assessment->lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus program asesmen ini.');
        }

        $assessmentId = $assessment->id;
        $title = $assessment->title;
        $assessment->delete();

        Log::info('Assessment deleted', [
            'assessment_id' => $assessmentId,
            'title' => $title,
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('assessments.index')->with('success', "Program asesmen {$title} berhasil dihapus.");
    }

    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $rawIds = $request->input('ids');
        if (is_string($rawIds)) {
            $rawIds = explode(',', $rawIds);
        }
        $ids = array_values(array_filter(array_map('intval', (array) $rawIds)));

        if (empty($ids)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada program asesmen yang dipilih.',
                ], 422);
            }
            return back()->with('error', 'Tidak ada program asesmen yang dipilih.');
        }

        $query = Assessment::with('lpk')->whereIn('id', $ids);
        if ($user && $user->isPic()) {
            $query->whereHas('lpk', function ($q) use ($user) {
                $q->where('pic_id', $user->id)
                    ->orWhereHas('members', fn ($mq) => $mq->where('lpk_members.user_id', $user->id)->where('lpk_members.role', 'lead'));
            });
        }

        $records = $query->get();
        $count = $records->count();

        if ($count === 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada program asesmen yang dapat dihapus atau Anda tidak memiliki hak akses.',
                ], 403);
            }
            return back()->with('error', 'Tidak ada program asesmen yang dapat dihapus.');
        }

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $record->delete();
            }
        });

        Log::info('Assessments bulk deleted', [
            'deleted_count' => $count,
            'deleted_ids' => $records->pluck('id')->all(),
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
        ]);

        $message = "{$count} program asesmen berhasil dihapus.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $count,
            ]);
        }

        return redirect()->route('assessments.index')->with('success', $message);
    }
}
