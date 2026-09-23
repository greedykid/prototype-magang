<?php

namespace App\Http\Controllers;

use App\Models\Lpk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LpkController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $surveillance = $request->string('surveillance')->toString();
        $expiry = $request->string('expiry')->toString();
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = Lpk::query()
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('registration_number', 'like', "%{$search}%")
                ->orWhere('scope', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
            ));
        if ($status === 'INACTIVE') {
            $query->where('status', 'INACTIVE');
        } elseif ($status === 'SURVEILLANCE_OVERDUE') {
            $matchingIds = Lpk::with('assessments')->get()->filter(fn (Lpk $lpk) => $lpk->dynamic_status === 'SURVEILLANCE_OVERDUE')->pluck('id');
            $query->whereIn('id', $matchingIds);
        } elseif ($status === 'SURVEILLANCE_DUE') {
            $matchingIds = Lpk::with('assessments')->get()->filter(fn (Lpk $lpk) => $lpk->dynamic_status === 'SURVEILLANCE_DUE')->pluck('id');
            $query->whereIn('id', $matchingIds);
        } elseif ($status === 'EXPIRED') {
            $query->whereNotNull('expired_at')->where('expired_at', '<', now()->startOfDay());
        } elseif ($status === 'ACTIVE') {
            $query->where('status', 'ACTIVE');
        }

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

        // Filter Status Siklus Pengawasan KAN (S1, S2, RA, Peringatan)
        if ($surveillance) {
            $matchingIds = Lpk::with('assessments')->get()->filter(function (Lpk $lpk) use ($surveillance) {
                $alerts = $lpk->getActiveSurveillanceAlerts();
                $milestones = $lpk->surveillance_milestones;

                return match ($surveillance) {
                    'NEEDS_ACTION' => count($alerts) > 0,
                    'DUE_S1' => in_array($milestones['s1']['status'] ?? '', ['DUE', 'OVERDUE'], true),
                    'DUE_S2' => in_array($milestones['s2']['status'] ?? '', ['DUE', 'OVERDUE'], true),
                    'DUE_RA' => in_array($milestones['ra']['status'] ?? '', ['DUE', 'OVERDUE', 'EXPIRED'], true),
                    'OVERDUE' => collect($alerts)->contains('is_urgent', true),
                    default => true,
                };
            })->pluck('id');

            $query->whereIn('id', $matchingIds);
        }

        $lpks = $query->with(['assessments'])->withCount(['accreditations', 'issues'])->latest()->paginate($perPage)->withQueryString();

        return view('lpks.index', compact('lpks', 'search', 'status', 'surveillance', 'expiry', 'perPage'));
    }

    public function create(): View
    {
        return view('lpks.form', ['lpk' => new Lpk, 'formTitle' => 'Tambah LPK']);
    }

    public function store(Request $request): RedirectResponse
    {
        $lpk = Lpk::create($this->validated($request));

        return redirect()->route('lpks.show', $lpk)->with('success', 'LPK berhasil ditambahkan.');
    }

    public function show(Lpk $lpk): View
    {
        return view('lpks.show', ['lpk' => $lpk->load(['accreditations', 'issues' => fn ($q) => $q->latest(), 'assessments'])]);
    }

    public function edit(Lpk $lpk): View
    {
        return view('lpks.form', ['lpk' => $lpk, 'formTitle' => 'Ubah Data LPK']);
    }

    public function update(Request $request, Lpk $lpk): RedirectResponse
    {
        $lpk->update($this->validated($request, $lpk));

        return redirect()->route('lpks.show', $lpk)->with('success', 'Data LPK berhasil diperbarui.');
    }

    public function destroy(Lpk $lpk): RedirectResponse
    {
        $name = $lpk->name;
        $reg = $lpk->registration_number;
        $lpk->delete();

        return redirect()->route('lpks.index')->with('success', "Data LPK {$name} ({$reg}) berhasil dihapus.");
    }

    public function sendSurveillanceReminder(Lpk $lpk, Request $request): RedirectResponse
    {
        try {
            $alerts = $lpk->getActiveSurveillanceAlerts();
            $isSimulation = $request->boolean('is_simulation') || $request->input('simulasi') == 1;

            if (empty($alerts) && ! $isSimulation) {
                return back()->with('error', 'Tidak ada jadwal notifikasi pengawasan (S1, S2, atau Re-Akreditasi) yang sedang aktif untuk LPK ini.');
            }

            $recipientEmail = $lpk->email;
            if (empty($recipientEmail)) {
                if ($isSimulation) {
                    $recipientEmail = auth()->user()?->email ?: 'sandbox@simasadi.test';
                } else {
                    return back()->with('error', 'LPK tidak memiliki alamat email PIC Lab yang terdaftar. Harap lengkapi email pada profil LPK terlebih dahulu.');
                }
            }

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
                    'lpk_email' => $recipientEmail,
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

            $username = env('MAIL_USERNAME') ?: config('mail.mailers.smtp.username') ?: 'dccf097e9f5ffe';
            $password = env('MAIL_PASSWORD') ?: config('mail.mailers.smtp.password') ?: 'ee0c1f3cad62a3';
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
                \Illuminate\Support\Facades\Mail::mailer('smtp')->to($recipientEmail)->send(new \App\Mail\SurveillanceReminderMail($lpk, $targetAlert));
            } catch (\Throwable $sendException) {
                // Auto-fallback dual-port: jika port 587/2525 terkendala di network lokal atau cloud, coba port pasangannya secara otomatis
                if (str_contains($host, 'mailtrap.io') && in_array($port, [587, 2525], true)) {
                    $altPort = ($port === 587) ? 2525 : 587;
                    \Illuminate\Support\Facades\Mail::purge('smtp');
                    config(['mail.mailers.smtp.port' => $altPort]);
                    \Illuminate\Support\Facades\Mail::mailer('smtp')->to($recipientEmail)->send(new \App\Mail\SurveillanceReminderMail($lpk, $targetAlert));
                } else {
                    throw $sendException;
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('lpks', 'last_surveillance_notified_at')) {
                $lpk->update(['last_surveillance_notified_at' => now()]);
            }

            $prefix = $isSimulation ? 'Simulasi email' : 'Email';
            return back()->with('success', "{$prefix} pemberitahuan resmi {$targetAlert['name']} berhasil dikirimkan ke {$recipientEmail} via Mailtrap SMTP.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Surveillance Reminder Mail Error: ' . $e->getMessage(), [
                'exception' => $e,
                'lpk_id' => $lpk->id,
            ]);
            return back()->with('error', 'Gagal mengirim email notifikasi: ' . $e->getMessage());
        }
    }

    private function validated(Request $request, ?Lpk $lpk = null): array
    {
        $data = $request->validate([
            'registration_number' => ['required', 'string', 'max:50', 'unique:lpks,registration_number,'.($lpk?->id ?? 'NULL')],
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:50000'],
            'certificate_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'expired_at' => ['nullable', 'date'],
            'drive_url' => ['nullable', 'url', 'max:1000'],
        ]);

        if (! empty($data['certificate_date']) && empty($data['expired_at'])) {
            $data['expired_at'] = \Carbon\Carbon::parse($data['certificate_date'])->addYears(5)->toDateString();
        } elseif (empty($data['certificate_date']) && ! empty($data['expired_at'])) {
            $data['certificate_date'] = \Carbon\Carbon::parse($data['expired_at'])->subYears(5)->toDateString();
        }

        return $data;
    }
}
