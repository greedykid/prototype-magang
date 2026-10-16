<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLpkRequest;
use App\Http\Requests\UpdateLpkNotesRequest;
use App\Http\Requests\UpdateLpkRequest;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LpkController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $surveillance = $request->string('surveillance')->toString();
        $expiry = $request->string('expiry')->toString();
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = Lpk::query()
            ->primaryFor($user)
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('registration_number', 'like', "%{$search}%")
                ->orWhere('no_reg', 'like', "%{$search}%")
                ->orWhere('accreditation_number', 'like', "%{$search}%")
                ->orWhere('accreditation_type', 'like', "%{$search}%")
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

        $sort = $request->input('sort');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'accreditation_number', 'registration_number', 'no_reg', 'created_at', 'expired_at'];

        if ($sort && in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $lpks = $query->with(['assessments', 'pic', 'members'])->paginate($perPage)->withQueryString();
        foreach ($lpks as $lpk) {
            foreach ($lpk->assessments as $assessment) {
                $assessment->setRelation('lpk', $lpk);
            }
        }

        if ($request->ajax() && $request->hasHeader('X-Partial-Content')) {
            return view('lpks.partials.table-content', compact('lpks', 'search', 'status', 'surveillance', 'expiry', 'perPage', 'sort', 'direction'));
        }

        $linkedOwnersCount = ($user && $user->isPic()) ? $user->linkedOwners()->count() : 0;

        return view('lpks.index', compact('lpks', 'search', 'status', 'surveillance', 'expiry', 'perPage', 'linkedOwnersCount', 'sort', 'direction'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $pics = $user && $user->isAdmin() ? User::where('role', User::ROLE_PIC)->orderBy('name')->get() : collect();

        return view('lpks.form', ['lpk' => new Lpk, 'formTitle' => 'Tambah LPK', 'pics' => $pics]);
    }

    public function store(StoreLpkRequest $request): RedirectResponse
    {
        $data = $request->normalizedData();
        $user = $request->user();
        if ($user && $user->isPic()) {
            $data['pic_id'] = $user->id;
        }

        $lpk = Lpk::create($data);

        return redirect()->route('lpks.index')->with('success', 'LPK berhasil ditambahkan.');
    }

    public function show(Lpk $lpk, Request $request): View
    {
        $user = $request->user();
        if ($user && ! $lpk->canView($user)) {
            abort(403, 'Anda tidak memiliki hak untuk melihat rincian LPK ini.');
        }

        $lpk->load(['assessments', 'pic.linkedViewers']);
        foreach ($lpk->assessments as $assessment) {
            $assessment->setRelation('lpk', $lpk);
        }

        return view('lpks.show', ['lpk' => $lpk]);
    }

    public function edit(Lpk $lpk, Request $request): View
    {
        $user = $request->user();
        if ($user && ! $lpk->canManage($user) && ! empty($lpk->pic_id)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah data LPK ini.');
        }

        $pics = $user && $user->isAdmin() ? User::where('role', User::ROLE_PIC)->orderBy('name')->get() : collect();

        return view('lpks.form', ['lpk' => $lpk, 'formTitle' => 'Ubah Data LPK', 'pics' => $pics]);
    }

    public function update(UpdateLpkRequest $request, Lpk $lpk): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $lpk->canManage($user) && ! empty($lpk->pic_id)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah data LPK ini.');
        }

        $data = $request->normalizedData();
        if ($user && $user->isPic()) {
            $data['pic_id'] = $lpk->pic_id ?: $user->id;
        }

        $lpk->update($data);

        return redirect()->route('lpks.show', $lpk)->with('success', 'Data LPK berhasil diperbarui.');
    }

    public function updateNotes(UpdateLpkNotesRequest $request, Lpk $lpk): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah catatan LPK ini.');
        }

        $data = $request->validated();
        $notes = isset($data['notes']) ? trim($data['notes']) : null;

        $lpk->update([
            'notes' => $notes !== '' ? $notes : null,
        ]);

        return redirect()->route('lpks.show', $lpk)->with('success', 'Keterangan LPK berhasil diperbarui.');
    }

    public function destroy(Lpk $lpk, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus data LPK ini.');
        }

        $lpkId = $lpk->id;
        $name = $lpk->name;
        $reg = $lpk->registration_number;

        DB::transaction(fn () => $lpk->delete());

        Log::info('LPK deleted', [
            'lpk_id' => $lpkId,
            'registration_number' => $reg,
            'lpk_name' => $name,
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('lpks.index')->with('success', "Data LPK {$name} ({$reg}) berhasil dihapus.");
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
                    'message' => 'Tidak ada data LPK yang dipilih.',
                ], 422);
            }
            return back()->with('error', 'Tidak ada data LPK yang dipilih.');
        }

        $records = Lpk::manageableBy($user)->whereIn('id', $ids)->get();
        $count = $records->count();

        if ($count === 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data LPK yang dapat dihapus atau Anda tidak memiliki hak akses.',
                ], 403);
            }
            return back()->with('error', 'Tidak ada data LPK yang dapat dihapus.');
        }

        $idsToDelete = $records->pluck('id')->all();

        DB::transaction(function () use ($idsToDelete) {
            \App\Models\Assessment::whereIn('lpk_id', $idsToDelete)->delete();
            DB::table('lpk_members')->whereIn('lpk_id', $idsToDelete)->delete();
            DB::table('calendar_events')->whereIn('lpk_id', $idsToDelete)->delete();
            Lpk::whereIn('id', $idsToDelete)->delete();
            \App\Services\LpkSurveillanceService::invalidateAlertsCache();
        });

        Log::info('LPKs bulk deleted', [
            'deleted_count' => $count,
            'deleted_ids' => $records->pluck('id')->all(),
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
        ]);

        $message = "{$count} data LPK berhasil dihapus.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $count,
            ]);
        }

        return redirect()->route('lpks.index')->with('success', $message);
    }

    public function sendSurveillanceReminder(Lpk $lpk, Request $request): RedirectResponse
    {
        try {
            $alerts = $lpk->getActiveSurveillanceAlerts();
            $isSimulation = $request->boolean('is_simulation') || $request->input('simulasi') == 1;

            if (empty($alerts) && ! $isSimulation) {
                return back()->with('error', 'Tidak ada jadwal notifikasi pengawasan (S1, S2, atau Re-Akreditasi) yang sedang aktif untuk LPK ini.');
            }

            // Alur Opsi 2: Prioritaskan pengiriman notifikasi kerja ke PIC penanggung jawab LPK ini
            $assignedEmails = [];
            if ($lpk->pic && ! empty($lpk->pic->email)) {
                $assignedEmails[] = $lpk->pic->email;
            }

            $leadMemberEmails = $lpk->members()
                ->wherePivot('role', 'lead')
                ->pluck('email')
                ->filter()
                ->all();

            $assignedEmails = array_values(array_unique(array_filter(array_merge($assignedEmails, $leadMemberEmails))));

            // Fallback: Jika LPK belum memiliki PIC yang ditugaskan, kirim ke seluruh PIC unit atau Admin
            if (! empty($assignedEmails)) {
                $picEmails = $assignedEmails;
            } else {
                $picEmails = \App\Models\User::where('role', \App\Models\User::ROLE_PIC)->pluck('email')->filter()->values()->all();
            }

            // Jika belum ada pengguna ber-peran PIC, fallback ke admin sistem atau user yang sedang login
            if (empty($picEmails)) {
                $picEmails = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->pluck('email')->filter()->values()->all();
            }

            if (empty($picEmails) && auth()->check() && auth()->user()?->email) {
                $picEmails = [auth()->user()->email];
            }

            if (empty($picEmails)) {
                $picEmails = [config('mail.from.address', 'simasadi@kan.or.id')];
            }

            $recipientEmails = $picEmails;
            $recipientEmail = $recipientEmails[0];

            $code = strtoupper((string) $request->input('code', ''));
            $targetAlert = null;
            if ($code && ! empty($alerts)) {
                foreach ($alerts as $a) {
                    if ($a['code'] === $code) {
                        $targetAlert = $a;
                        break;
                    }
                }
            }

            if (! $targetAlert && ! empty($alerts) && ! $code) {
                $targetAlert = $alerts[0];
            }

            if (! $targetAlert) {
                $milestones = $lpk->surveillance_milestones;
                $mKey = strtolower($code) ?: 's1';
                $selectedM = $milestones[$mKey] ?? $milestones['s1'];
                $targetAlert = [
                    'lpk_id' => $lpk->id,
                    'lpk_reg' => $lpk->registration_number,
                    'lpk_name' => $lpk->name,
                    'lpk_email' => $lpk->email ?: $recipientEmail,
                    'code' => $selectedM['code'],
                    'name' => $selectedM['name'] . ($isSimulation ? ' (Simulasi)' : ''),
                    'notice_date' => $selectedM['notice_date'],
                    'target_date' => $selectedM['target_date'],
                    'status' => 'SIMULATED',
                    'status_label' => 'SIMULASI UJI COBA',
                    'is_urgent' => true,
                    'severity' => 'warn',
                    'description' => $selectedM['description'],
                    'last_notified_at' => $lpk->last_surveillance_notified_at ?? null,
                ];
            }

            \Illuminate\Support\Facades\Mail::to($recipientEmails)->queue(new \App\Mail\SurveillanceReminderMail($lpk, $targetAlert));

            $lpk->update(['last_surveillance_notified_at' => now()]);

            $recipientCount = count($recipientEmails);
            $recipientSummary = $recipientCount === 1 ? $recipientEmails[0] : "{$recipientCount} PIC Unit (" . implode(', ', $recipientEmails) . ')';
            $prefix = $isSimulation ? 'Simulasi email pengingat' : 'Email pengingat';
            return back()->with('success', "{$prefix} jadwal {$targetAlert['name']} untuk {$lpk->name} berhasil dimasukkan ke antrean pengiriman ke PIC internal unit BSN ({$recipientSummary}).");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Surveillance Reminder Mail Error: ' . $e->getMessage(), [
                'exception' => $e,
                'lpk_id' => $lpk->id,
            ]);
            return back()->with('error', 'Gagal mengirim email notifikasi: ' . $e->getMessage());
        }
    }
}
