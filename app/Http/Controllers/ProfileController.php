<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan formulir pengaturan profil dan kata sandi pengguna.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $linkedViewers = $user->linkedViewers()
            ->withCount('lpks')
            ->orderBy('name')
            ->get();

        $linkedOwners = $user->linkedOwners()
            ->withCount('lpks')
            ->orderBy('name')
            ->get();

        $alreadyViewerIds = $linkedViewers->pluck('id')->push($user->id)->all();

        $availableUsers = \App\Models\User::whereNotIn('id', $alreadyViewerIds)
            ->where('role', \App\Models\User::ROLE_PIC)
            ->orderBy('name')
            ->get();

        return view('profile.edit', compact('user', 'linkedViewers', 'linkedOwners', 'availableUsers'));
    }

    /**
     * Perbarui data informasi profil pengguna (nama dan email).
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Data profil Anda berhasil diperbarui.');
    }

    /**
     * Perbarui kata sandi akun pengguna dengan verifikasi kata sandi saat ini.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Kata sandi Anda berhasil diperbarui.');
    }
}
