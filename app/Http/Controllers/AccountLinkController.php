<?php

namespace App\Http\Controllers;

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

        $alreadyViewerIds = $linkedViewers->pluck('id')->push($user->id)->all();

        $availableUsers = User::whereNotIn('id', $alreadyViewerIds)
            ->where('role', User::ROLE_PIC)
            ->orderBy('name')
            ->get();

        return view('account-links.index', compact('user', 'linkedViewers', 'linkedOwners', 'availableUsers'));
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
