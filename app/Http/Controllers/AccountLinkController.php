<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountLinkController extends Controller
{
    /**
     * Tampilkan halaman kelola tautan akun (viewer lintas akun).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Akun-akun viewer yang kita beri izin melihat seluruh LPK dan agenda kita
        $linkedViewers = $user->linkedViewers()
            ->withCount('lpks')
            ->orderBy('name')
            ->get();

        // Akun-akun pemilik data yang memberi kita izin viewer melihat LPK dan agenda mereka
        $linkedOwners = $user->linkedOwners()
            ->withCount('lpks')
            ->orderBy('name')
            ->get();

        $ownerIds = $linkedOwners->pluck('id')->all();
        $assessmentCounts = [];
        if (! empty($ownerIds)) {
            $leadCounts = DB::table('assessments')
                ->join('lpks', 'assessments.lpk_id', '=', 'lpks.id')
                ->whereIn('lpks.pic_id', $ownerIds)
                ->selectRaw('lpks.pic_id as user_id, count(assessments.id) as total')
                ->groupBy('lpks.pic_id')
                ->pluck('total', 'user_id');

            $memberCounts = DB::table('assessments')
                ->join('lpk_members', 'assessments.lpk_id', '=', 'lpk_members.lpk_id')
                ->whereIn('lpk_members.user_id', $ownerIds)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('lpks')
                        ->whereColumn('lpks.id', 'assessments.lpk_id')
                        ->whereColumn('lpks.pic_id', 'lpk_members.user_id');
                })
                ->selectRaw('lpk_members.user_id, count(assessments.id) as total')
                ->groupBy('lpk_members.user_id')
                ->pluck('total', 'user_id');

            foreach ($ownerIds as $ownerId) {
                $assessmentCounts[$ownerId] = ($leadCounts[$ownerId] ?? 0) + ($memberCounts[$ownerId] ?? 0);
            }
        }

        foreach ($linkedOwners as $owner) {
            $owner->assessments_count = $assessmentCounts[$owner->id] ?? 0;
        }

        $alreadyViewerIds = $linkedViewers->pluck('id')->push($user->id)->all();

        $availableUsers = User::whereNotIn('id', $alreadyViewerIds)
            ->where('role', User::ROLE_PIC)
            ->orderBy('name')
            ->get();

        $allPics = $user->isAdmin()
            ? User::where('role', User::ROLE_PIC)->orderBy('name')->get()
            : collect();

        return view('account-links.index', compact('user', 'linkedViewers', 'linkedOwners', 'availableUsers', 'allPics'));
    }

    /**
     * Tampilkan detail akun tertaut beserta daftar LPK mandiri milik akun tersebut.
     */
    public function show(User $user, Request $request): View
    {
        $currentUser = $request->user();

        // Otorisasi: Pengguna harus Admin, pemilik akun itu sendiri, atau memiliki akses viewer ke akun tersebut
        $canAccess = $currentUser->isAdmin()
            || (int) $currentUser->id === (int) $user->id
            || $currentUser->isViewerFor($user);

        if (! $canAccess) {
            abort(403, 'Anda tidak memiliki hak akses untuk memantau data akun ini.');
        }

        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['lpks', 'assessments'], true)) {
            $tab = 'lpks';
        }

        $owner = $user;
        $perPage = $request->integer('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $isPartial = $request->ajax() && $request->hasHeader('X-Partial-Content');

        $totalLpkCount = 0;
        $activeLpkCount = 0;
        $totalAssessmentCount = 0;
        $activeAssessmentCount = 0;
        $completedAssessmentCount = 0;
        $ownerLpks = collect();
        $assessmentTypes = Assessment::TYPES;

        if (! $isPartial) {
            $totalLpkCount = Lpk::primaryFor($owner)->count();
            $activeLpkCount = Lpk::primaryFor($owner)->where('status', 'ACTIVE')->count();
            $totalAssessmentCount = Assessment::whereHas('lpk', fn ($lq) => $lq->primaryFor($owner))->count();
            $activeAssessmentCount = Assessment::whereHas('lpk', fn ($lq) => $lq->primaryFor($owner))->whereIn('status', ['SCHEDULED', 'IN_PROGRESS'])->count();
            $completedAssessmentCount = Assessment::whereHas('lpk', fn ($lq) => $lq->primaryFor($owner))->where('status', 'COMPLETED')->count();
            $ownerLpks = Lpk::primaryFor($owner)->orderBy('name')->get();
        }

        if ($tab === 'assessments') {
            $search = $request->string('search')->trim()->toString();
            $lpkId = $request->input('lpk_id');
            $assessmentType = $request->string('assessment_type')->toString();
            $status = $request->string('status')->toString();
            $tpFilter = $request->string('tp_status')->toString();
            $startFrom = $request->input('start_from');
            $startTo = $request->input('start_to');

            $assessmentQuery = Assessment::query()
                ->with(['lpk'])
                ->whereHas('lpk', fn ($lq) => $lq->primaryFor($owner))
                ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('sk_number', 'like', "%{$search}%")
                      ->orWhereHas('lpk', fn ($lpkQ) => $lpkQ
                          ->where('name', 'like', "%{$search}%")
                          ->orWhere('registration_number', 'like', "%{$search}%")
                      );
                }))
                ->when($lpkId, fn ($query) => $query->where('lpk_id', $lpkId))
                ->when($assessmentType, fn ($query) => $query->forAssessmentType($assessmentType))
                ->when($status, function ($query, $st) {
                    if ($st === 'SUSPENDED') {
                        $query->where(function ($q) {
                            $q->where('status', 'SUSPENDED')
                                ->orWhere(fn ($sub) => $sub->tpOverdue());
                        });
                    } elseif ($st === 'IN_PROGRESS') {
                        $query->where('status', 'IN_PROGRESS')
                            ->whereNotIn('id', Assessment::select('id')->tpOverdue());
                    } else {
                        $query->where('status', $st);
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
                ->when($startTo, fn ($query) => $query->whereDate('start_at', '<=', $startTo));

            $assessments = $assessmentQuery->latest('start_at')->paginate($perPage)->withQueryString();

            if ($request->ajax() && $request->hasHeader('X-Partial-Content')) {
                return view('account-links.partials.assessments-table-content', compact(
                    'currentUser', 'owner', 'assessments', 'perPage',
                    'search', 'lpkId', 'assessmentType', 'status', 'tpFilter', 'startFrom', 'startTo'
                ));
            }

            return view('account-links.show', compact(
                'currentUser', 'owner', 'tab', 'assessments', 'perPage',
                'totalLpkCount', 'activeLpkCount', 'totalAssessmentCount', 'activeAssessmentCount', 'completedAssessmentCount',
                'ownerLpks', 'assessmentTypes',
                'search', 'lpkId', 'assessmentType', 'status', 'tpFilter', 'startFrom', 'startTo'
            ));
        }

        // Tab default: LPKs
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $surveillance = $request->string('surveillance')->toString();
        $expiry = $request->string('expiry')->toString();

        // Query khusus LPK milik akun tertaut ini saja (terpisah dari daftar LPK utama)
        $query = Lpk::primaryFor($owner)
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('no_reg', 'like', "%{$search}%")
                ->orWhere('accreditation_number', 'like', "%{$search}%")
                ->orWhere('accreditation_type', 'like', "%{$search}%")
                ->orWhere('registration_number', 'like', "%{$search}%")
                ->orWhere('scope', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
            ));

        // Filter Masa Berlaku Sertifikat Akreditasi
        if ($expiry === 'EXPIRED') {
            $query->whereNotNull('expired_at')->where('expired_at', '<', now()->startOfDay());
        } elseif ($expiry === 'EXPIRING_SOON') {
            $query->whereNotNull('expired_at')
                ->where('expired_at', '>=', now()->startOfDay())
                ->where('expired_at', '<=', now()->addDays(90)->endOfDay());
        } elseif ($expiry === 'VALID') {
            $query->whereNotNull('expired_at')->where('expired_at', '>', now()->addDays(90)->endOfDay());
        }

        if ($status === 'INACTIVE') {
            $query->where('status', 'INACTIVE');
        }

        $needsDynamicStatusFilter = in_array($status, [
            'GRACE_PERIOD', 'REVOKED', 'SUSPENDED', 'SURVEILLANCE_OVERDUE', 'SURVEILLANCE_DUE', 'EXPIRED', 'ACTIVE',
        ], true);
        $needsSurveillanceFilter = ! empty($surveillance);

        if ($needsDynamicStatusFilter || $needsSurveillanceFilter) {
            $candidateQuery = (clone $query)
                ->select(['id', 'status', 'certificate_date', 'expired_at', 'last_surveillance_notified_at'])
                ->with(['assessments' => function ($q) {
                    $q->select([
                        'id', 'lpk_id', 'title', 'assessment_type', 'start_at', 'end_at',
                        'status', 'tp_status', 'tp_satisfied_at', 'submission_due_date',
                        'report_date', 'eha_date', 'sk_number', 'created_at',
                    ]);
                }]);

            // Pre-filter level database untuk memangkas footprint RAM dan jumlah kandidat
            if ($status === 'EXPIRED') {
                $candidateQuery->whereNotNull('expired_at')->where('expired_at', '<', now());
            } elseif ($status === 'ACTIVE') {
                $candidateQuery->where('status', '!=', 'INACTIVE');
            } elseif ($status === 'GRACE_PERIOD') {
                $candidateQuery->whereNotNull('expired_at')
                    ->where('expired_at', '<', now())
                    ->where('expired_at', '>=', now()->subMonths(6));
            } elseif ($status === 'REVOKED') {
                $candidateQuery->where(function ($q) {
                    $q->whereIn('status', ['REVOKED', 'DICABUT'])
                        ->orWhere('expired_at', '<', now()->subMonths(6))
                        ->orWhereHas('assessments', fn ($aq) => $aq->where('status', 'REVOKED'));
                });
            } elseif ($status === 'SUSPENDED') {
                $candidateQuery->where(function ($q) {
                    $q->where('status', 'SUSPENDED')
                        ->orWhereHas('assessments', fn ($aq) => $aq->where('status', 'SUSPENDED')->orWhere('tp_status', 'IN_PROGRESS'));
                });
            } elseif (in_array($status, ['SURVEILLANCE_OVERDUE', 'SURVEILLANCE_DUE'], true)) {
                $candidateQuery->whereNotNull('certificate_date')->where('status', '!=', 'INACTIVE');
            }

            if (in_array($surveillance, ['DUE_S1', 'DUE_S2', 'OVERDUE'], true)) {
                $candidateQuery->whereNotNull('certificate_date');
            } elseif ($surveillance === 'DUE_RA') {
                $candidateQuery->where(fn ($q) => $q->whereNotNull('certificate_date')->orWhereNotNull('expired_at'));
            }

            $validIds = [];
            $candidateQuery->chunkById(150, function ($candidateChunk) use (
                &$validIds,
                $needsDynamicStatusFilter,
                $status,
                $needsSurveillanceFilter,
                $surveillance
            ) {
                foreach ($candidateChunk as $candidate) {
                    foreach ($candidate->assessments as $asm) {
                        $asm->setRelation('lpk', $candidate);
                    }

                    if ($needsDynamicStatusFilter && $candidate->dynamic_status !== $status) {
                        continue;
                    }

                    if ($needsSurveillanceFilter) {
                        $alerts = $candidate->getActiveSurveillanceAlerts();
                        $milestones = $candidate->surveillance_milestones;

                        $matched = match ($surveillance) {
                            'NEEDS_ACTION' => count($alerts) > 0,
                            'DUE_S1' => in_array($milestones['s1']['status'] ?? '', ['DUE', 'OVERDUE', 'SUSPENDED'], true),
                            'DUE_S2' => in_array($milestones['s2']['status'] ?? '', ['DUE', 'OVERDUE', 'SUSPENDED'], true),
                            'DUE_RA' => in_array($milestones['ra']['status'] ?? '', ['DUE', 'OVERDUE', 'EXPIRED', 'SUSPENDED'], true),
                            'OVERDUE' => collect($alerts)->contains('is_urgent', true),
                            default => true,
                        };

                        if (! $matched) {
                            continue;
                        }
                    }

                    $validIds[] = $candidate->id;
                }
            });

            if (empty($validIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $validIds);
            }
        }

        $lpks = $query->with(['assessments', 'pic'])->latest()->paginate($perPage)->withQueryString();
        foreach ($lpks as $lpk) {
            foreach ($lpk->assessments as $assessment) {
                $assessment->setRelation('lpk', $lpk);
            }
        }

        if ($request->ajax() && $request->hasHeader('X-Partial-Content')) {
            return view('account-links.partials.table-content', compact(
                'currentUser',
                'owner',
                'lpks',
                'search',
                'status',
                'surveillance',
                'expiry',
                'perPage'
            ));
        }

        return view('account-links.show', compact(
            'currentUser',
            'owner',
            'tab',
            'lpks',
            'search',
            'status',
            'surveillance',
            'expiry',
            'perPage',
            'totalLpkCount',
            'activeLpkCount',
            'totalAssessmentCount',
            'activeAssessmentCount',
            'completedAssessmentCount',
            'ownerLpks',
            'assessmentTypes'
        ));
    }

    /**
     * Tampilkan rincian LPK dalam konteks pemantauan akun tertaut.
     */
    public function showLpk(User $user, Lpk $lpk, Request $request): View
    {
        $currentUser = $request->user();

        $canAccess = $currentUser->isAdmin()
            || (int) $currentUser->id === (int) $user->id
            || $currentUser->isViewerFor($user);

        if (! $canAccess) {
            abort(403, 'Anda tidak memiliki hak akses untuk memantau data akun ini.');
        }

        $isOwnerLpk = (int) $lpk->pic_id === (int) $user->id
            || $lpk->members()->where('users.id', $user->id)->exists();

        if (! $isOwnerLpk) {
            abort(404, 'Laboratorium tidak ditemukan pada akun tertaut ini.');
        }

        $owner = $user;
        $lpk->load([
            'pic',
            'assessments' => fn ($q) => $q->orderBy('start_at', 'desc'),
            'members',
        ]);

        return view('account-links.lpks.show', compact('owner', 'lpk', 'currentUser'));
    }

    /**
     * Tampilkan rincian agenda asesmen dalam konteks pemantauan akun tertaut.
     */
    public function showAssessment(User $user, Assessment $assessment, Request $request): View
    {
        $currentUser = $request->user();

        $canAccess = $currentUser->isAdmin()
            || (int) $currentUser->id === (int) $user->id
            || $currentUser->isViewerFor($user);

        if (! $canAccess) {
            abort(403, 'Anda tidak memiliki hak akses untuk memantau data akun ini.');
        }

        $assessment->load(['lpk.members', 'lpk.pic']);

        $isOwnerAssessment = (int) $assessment->lpk?->pic_id === (int) $user->id
            || ($assessment->lpk && $assessment->lpk->members->contains('id', $user->id));

        if (! $isOwnerAssessment) {
            abort(404, 'Agenda asesmen tidak ditemukan pada akun tertaut ini.');
        }

        $owner = $user;
        $assessment->load(['lpk.pic', 'creator']);

        return view('account-links.assessments.show', compact('owner', 'assessment', 'currentUser'));
    }

    /**
     * Tautkan akun PIC lain sebagai viewer (izin baca seluruh LPK, asesmen, kalender).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin() && $request->filled('owner_id')) {
            $user = User::findOrFail($request->integer('owner_id'));
        }

        $validated = $request->validate([
            'viewer_id' => ['required', 'integer', 'exists:users,id'],
        ], [
            'viewer_id.required' => 'Pilih akun yang ingin ditautkan sebagai viewer.',
            'viewer_id.exists' => 'Akun yang dipilih tidak ditemukan dalam sistem.',
        ]);

        $viewerId = (int) $validated['viewer_id'];

        if ($viewerId === (int) $user->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'viewer_id' => 'Anda tidak dapat menautkan akun Anda sendiri sebagai viewer.',
            ]);
        }

        $viewer = User::findOrFail($viewerId);

        if ($user->linkedViewers()->where('users.id', $viewer->id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'viewer_id' => "Akun {$viewer->name} sudah memiliki akses viewer ke akun Anda.",
            ]);
        }

        $user->linkedViewers()->attach($viewer->id);

        return redirect()->back(fallback: route('account-links.index'))->with('success', "Akun {$viewer->name} ({$viewer->email}) berhasil ditautkan sebagai Viewer. Seluruh daftar LPK, agenda asesmen, dan kalender pengawasan Anda sekarang otomatis muncul pada akun tersebut.");
    }

    /**
     * Putuskan tautan akun viewer.
     */
    public function destroy(User $user, Request $request): RedirectResponse
    {
        $currentUser = $request->user();

        // Skenario 1: Admin memutuskan tautan antar dua akun
        if ($currentUser->isAdmin() && $request->filled('owner_id')) {
            $owner = User::find($request->integer('owner_id'));
            if ($owner && $owner->linkedViewers()->where('users.id', $user->id)->exists()) {
                $owner->linkedViewers()->detach($user->id);
                return redirect()->back(fallback: route('account-links.index'))->with('success', "Tautan viewer antara {$owner->name} dan {$user->name} berhasil diputuskan.");
            }
        }

        // Skenario 2: Pemilik data memutuskan akses akun viewer
        if ($currentUser->linkedViewers()->where('users.id', $user->id)->exists()) {
            $currentUser->linkedViewers()->detach($user->id);
            return redirect()->back(fallback: route('account-links.index'))->with('success', "Akses viewer untuk akun {$user->name} berhasil diputuskan. Akun tersebut tidak lagi dapat melihat data LPK dan agenda Anda.");
        }

        // Skenario 3: Akun viewer melepaskan diri dari pantauan pemilik data
        if ($currentUser->linkedOwners()->where('users.id', $user->id)->exists()) {
            $currentUser->linkedOwners()->detach($user->id);
            return redirect()->back(fallback: route('account-links.index'))->with('success', "Anda berhasil melepaskan akses pemantauan dari akun {$user->name}.");
        }

        return redirect()->back(fallback: route('account-links.index'))->with('error', 'Tautan akun tidak ditemukan atau Anda tidak memiliki hak akses.');
    }
}
