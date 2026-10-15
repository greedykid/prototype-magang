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
            $lpkStats = Lpk::primaryFor($owner)
                ->selectRaw("
                    count(*) as total,
                    count(case when status = 'ACTIVE' then 1 end) as active
                ")
                ->first();
            $totalLpkCount = (int) ($lpkStats->total ?? 0);
            $activeLpkCount = (int) ($lpkStats->active ?? 0);

            $assessmentStats = Assessment::whereHas('lpk', fn ($lq) => $lq->primaryFor($owner))
                ->selectRaw("
                    count(*) as total,
                    count(case when status in ('SCHEDULED', 'IN_PROGRESS') then 1 end) as active,
                    count(case when status = 'COMPLETED' then 1 end) as completed
                ")
                ->first();
            $totalAssessmentCount = (int) ($assessmentStats->total ?? 0);
            $activeAssessmentCount = (int) ($assessmentStats->active ?? 0);
            $completedAssessmentCount = (int) ($assessmentStats->completed ?? 0);

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

            $assessments = Assessment::query()
                ->with(['lpk'])
                ->whereHas('lpk', fn ($lq) => $lq->primaryFor($owner))
                ->filterByCriteria([
                    'search' => $search,
                    'lpk_id' => $lpkId,
                    'assessment_type' => $assessmentType,
                    'status' => $status,
                    'tp_status' => $tpFilter,
                    'start_from' => $startFrom,
                    'start_to' => $startTo,
                ])
                ->latest('start_at')
                ->paginate($perPage)
                ->withQueryString();

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

        $query->filterDynamicStatusAndSurveillance($status, $surveillance);

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

        if (! $lpk->isPrimaryFor($user)) {
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

        if (! $assessment->isPrimaryFor($user)) {
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
