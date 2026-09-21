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
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $lpks = Lpk::query()->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%")->orWhere('scope', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))->when(in_array($status, ['ACTIVE', 'INACTIVE'], true), fn ($query) => $query->where('status', $status))->withCount(['accreditations', 'issues'])->latest()->paginate($perPage)->withQueryString();

        return view('lpks.index', compact('lpks', 'search', 'status', 'perPage'));
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
        return view('lpks.show', ['lpk' => $lpk->load(['accreditations', 'issues' => fn ($q) => $q->latest()])]);
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
        $alerts = $lpk->getActiveSurveillanceAlerts();
        if (empty($alerts)) {
            return back()->with('error', 'Tidak ada jadwal notifikasi pengawasan (S1, S2, atau Re-Akreditasi) yang sedang aktif untuk LPK ini.');
        }

        if (empty($lpk->email)) {
            return back()->with('error', 'LPK tidak memiliki alamat email PIC Lab yang terdaftar. Harap lengkapi email pada profil LPK terlebih dahulu.');
        }

        $code = $request->input('code');
        $targetAlert = null;
        if ($code) {
            foreach ($alerts as $a) {
                if ($a['code'] === $code) {
                    $targetAlert = $a;
                    break;
                }
            }
        }
        $alertToSend = $targetAlert ?: $alerts[0];

        try {
            \Illuminate\Support\Facades\Mail::to($lpk->email)->send(new \App\Mail\SurveillanceReminderMail($lpk, $alertToSend));
            $lpk->update(['last_surveillance_notified_at' => now()]);

            return back()->with('success', "Email pemberitahuan resmi {$alertToSend['name']} berhasil dikirimkan ke PIC Lab ({$lpk->email}) via Mailtrap SMTP.");
        } catch (\Throwable $e) {
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
        }

        return $data;
    }
}
