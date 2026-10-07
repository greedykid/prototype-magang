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
            return view('lpks.partials.table-content', compact('lpks', 'search', 'status', 'surveillance', 'expiry', 'perPage'));
        }

        $linkedOwnersCount = ($user && $user->isPic()) ? $user->linkedOwners()->count() : 0;

        return view('lpks.index', compact('lpks', 'search', 'status', 'surveillance', 'expiry', 'perPage', 'linkedOwnersCount'));
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

        return view('lpks.show', ['lpk' => $lpk->load(['accreditations', 'assessments', 'pic.linkedViewers', 'members'])]);
    }

    public function edit(Lpk $lpk, Request $request): View
    {
        $user = $request->user();
        if ($user && ! $lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah data LPK ini.');
        }

        $pics = $user && $user->isAdmin() ? User::where('role', User::ROLE_PIC)->orderBy('name')->get() : collect();

        return view('lpks.form', ['lpk' => $lpk, 'formTitle' => 'Ubah Data LPK', 'pics' => $pics]);
    }

    public function update(UpdateLpkRequest $request, Lpk $lpk): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $lpk->canManage($user)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah data LPK ini.');
        }

        $data = $request->normalizedData();
        if ($user && $user->isPic()) {
            $data['pic_id'] = $lpk->pic_id ?: $user->id;
        }

        $lpk->update($data);

        return redirect()->route('lpks.index')->with('success', 'Data LPK berhasil diperbarui.');
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
        $lpk->delete();

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

        $query = Lpk::whereIn('id', $ids);
        if ($user && $user->isPic()) {
            $query->where(function ($q) use ($user) {
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
                    'message' => 'Tidak ada data LPK yang dapat dihapus atau Anda tidak memiliki hak akses.',
                ], 403);
            }
            return back()->with('error', 'Tidak ada data LPK yang dapat dihapus.');
        }

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $record->delete();
            }
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

            // Alur Opsi 2: Notifikasi kerja internal ditujukan ke staf/PIC internal Unit Akreditasi Lab BSN
            $picEmails = \App\Models\User::where('role', \App\Models\User::ROLE_PIC)->pluck('email')->filter()->values()->all();

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

            // Konfigurasi SMTP dinamis: Cegah host 127.0.0.1 lokal dan dukung port 587/2525 secara otomatis
            $host = env('MAIL_HOST');
            if (empty($host) || $host === '127.0.0.1') {
                $host = 'sandbox.smtp.mailtrap.io';
            }

            $port = (int) (env('MAIL_PORT') ?: config('mail.mailers.smtp.port') ?: 587);
            if ($port !== 587 && $port !== 2525 && empty($port)) {
                $port = 587;
            }

            $username = env('MAIL_USERNAME') ?: config('mail.mailers.smtp.username');
            $password = env('MAIL_PASSWORD') ?: config('mail.mailers.smtp.password');
            $encryption = env('MAIL_ENCRYPTION') ?: config('mail.mailers.smtp.encryption') ?: 'tls';

            // Selalu bersihkan cache mailer agar instance lama di php artisan serve tidak digunakan
            \Illuminate\Support\Facades\Mail::purge('smtp');
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.mailers.smtp.encryption' => $encryption,
                'mail.mailers.smtp.timeout' => 10,
            ]);

            try {
                \Illuminate\Support\Facades\Mail::mailer('smtp')->to($recipientEmails)->send(new \App\Mail\SurveillanceReminderMail($lpk, $targetAlert));
            } catch (\Throwable $sendException) {
                // Auto-fallback dual-port: jika port 587/2525 terkendala di network lokal atau cloud, coba port pasangannya secara otomatis
                if (str_contains($host, 'mailtrap.io') && in_array($port, [587, 2525], true)) {
                    $altPort = ($port === 587) ? 2525 : 587;
                    \Illuminate\Support\Facades\Mail::purge('smtp');
                    config(['mail.mailers.smtp.port' => $altPort]);
                    \Illuminate\Support\Facades\Mail::mailer('smtp')->to($recipientEmails)->send(new \App\Mail\SurveillanceReminderMail($lpk, $targetAlert));
                } else {
                    throw $sendException;
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('lpks', 'last_surveillance_notified_at')) {
                $lpk->update(['last_surveillance_notified_at' => now()]);
            }

            $recipientCount = count($recipientEmails);
            $recipientSummary = $recipientCount === 1 ? $recipientEmails[0] : "{$recipientCount} PIC Unit (" . implode(', ', $recipientEmails) . ')';
            $prefix = $isSimulation ? 'Simulasi email pengingat' : 'Email pengingat';
            return back()->with('success', "{$prefix} jadwal {$targetAlert['name']} untuk {$lpk->name} berhasil dikirimkan ke PIC internal unit BSN ({$recipientSummary}).");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Surveillance Reminder Mail Error: ' . $e->getMessage(), [
                'exception' => $e,
                'lpk_id' => $lpk->id,
            ]);
            return back()->with('error', 'Gagal mengirim email notifikasi: ' . $e->getMessage());
        }
    }
}
