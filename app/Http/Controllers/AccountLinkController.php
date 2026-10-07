<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        foreach ($linkedOwners as $owner) {
            $owner->assessments_count = Assessment::whereHas('lpk', fn ($q) => $q->where('pic_id', $owner->id))->count();
        }

        $alreadyViewerIds = $linkedViewers->pluck('id')->push($user->id)->all();

        $availableUsers = User::whereNotIn('id', $alreadyViewerIds)
            ->where('role', User::ROLE_PIC)
            ->orderBy('name')
            ->get();

        return view('account-links.index', compact('user', 'linkedViewers', 'linkedOwners', 'availableUsers'));
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

        $totalLpkCount = Lpk::where('pic_id', $owner->id)->count();
        $activeLpkCount = Lpk::where('pic_id', $owner->id)->where('status', 'ACTIVE')->count();
        $totalAssessmentCount = Assessment::whereHas('lpk', fn ($lq) => $lq->where('pic_id', $owner->id))->count();
        $activeAssessmentCount = Assessment::whereHas('lpk', fn ($lq) => $lq->where('pic_id', $owner->id))->whereIn('status', ['SCHEDULED', 'IN_PROGRESS'])->count();
        $completedAssessmentCount = Assessment::whereHas('lpk', fn ($lq) => $lq->where('pic_id', $owner->id))->where('status', 'COMPLETED')->count();
        $ownerLpks = Lpk::where('pic_id', $owner->id)->orderBy('name')->get();
        $assessmentTypes = Assessment::TYPES;

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
                ->whereHas('lpk', fn ($lq) => $lq->where('pic_id', $owner->id))
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
        $query = Lpk::query()
            ->where(function ($q) use ($owner) {
                $q->where('lpks.pic_id', $owner->id)
                  ->orWhereHas('members', fn ($mq) => $mq->where('lpk_members.user_id', $owner->id));
            })
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
            $candidateLpks = (clone $query)->with('assessments')->get();
            foreach ($candidateLpks as $candidate) {
                foreach ($candidate->assessments as $asm) {
                    $asm->setRelation('lpk', $candidate);
                }
            }

            if ($needsDynamicStatusFilter) {
                $candidateLpks = $candidateLpks->filter(fn (Lpk $lpk) => $lpk->dynamic_status === $status);
            }

            if ($needsSurveillanceFilter) {
                $candidateLpks = $candidateLpks->filter(function (Lpk $lpk) use ($surveillance) {
                    $alerts = $lpk->getActiveSurveillanceAlerts();
                    $milestones = $lpk->surveillance_milestones;

                    return match ($surveillance) {
                        'NEEDS_ACTION' => count($alerts) > 0,
                        'DUE_S1' => in_array($milestones['s1']['status'] ?? '', ['DUE', 'OVERDUE', 'SUSPENDED'], true),
                        'DUE_S2' => in_array($milestones['s2']['status'] ?? '', ['DUE', 'OVERDUE', 'SUSPENDED'], true),
                        'DUE_RA' => in_array($milestones['ra']['status'] ?? '', ['DUE', 'OVERDUE', 'EXPIRED', 'SUSPENDED'], true),
                        'OVERDUE' => collect($alerts)->contains('is_urgent', true),
                        default => true,
                    };
                });
            }

            $query->whereIn('id', $candidateLpks->pluck('id'));
        }

        $lpks = $query->with(['assessments', 'pic'])->withCount(['accreditations'])->latest()->paginate($perPage)->withQueryString();
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
