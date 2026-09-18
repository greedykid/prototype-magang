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
        $lpks = Lpk::query()->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%")))->when(in_array($status, ['ACTIVE', 'INACTIVE'], true), fn ($query) => $query->where('status', $status))->withCount(['accreditations', 'issues'])->latest()->paginate(10)->withQueryString();

        return view('lpks.index', compact('lpks', 'search', 'status'));
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

    private function validated(Request $request, ?Lpk $lpk = null): array
    {
        return $request->validate(['registration_number' => ['required', 'string', 'max:50', 'unique:lpks,registration_number,'.($lpk?->id ?? 'NULL')], 'name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:50'], 'status' => ['required', 'in:ACTIVE,INACTIVE'], 'notes' => ['nullable', 'string']]);
    }
}
