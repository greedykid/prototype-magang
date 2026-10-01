<?php

namespace App\Http\Controllers;

use App\Models\Lpk;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LpkMemberController extends Controller
{
    /**
     * Tautkan PIC lain ke dalam tim kolaborasi LPK.
     */
    public function store(Request $request, Lpk $lpk): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $lpk->canManage($currentUser)) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola tim PIC pada LPK ini.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['nullable', 'in:lead,viewer'],
        ], [
            'user_id.required' => 'Pilih PIC yang ingin ditautkan.',
            'user_id.exists' => 'Akun PIC tidak ditemukan.',
            'role.in' => 'Peran harus berupa PIC Pendamping atau Viewer.',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        // Cegah menautkan PIC yang sudah menjadi pemilik utama
        if ($lpk->pic_id !== null && (int) $lpk->pic_id === (int) $targetUser->id) {
            $mainRole = $targetUser->isAdmin() ? 'Ketua Tim' : 'PIC Utama';
            return back()->with('error', "{$targetUser->name} sudah menjadi {$mainRole} pada LPK ini.");
        }

        // Cegah duplikasi penautan
        if ($lpk->members()->where('user_id', $targetUser->id)->exists()) {
            return back()->with('error', "PIC {$targetUser->name} sudah terhubung ke dalam tim LPK ini.");
        }

        $role = $validated['role'] ?? 'viewer';
        $lpk->members()->attach($targetUser->id, ['role' => $role]);

        $roleLabel = $role === 'lead' ? ($targetUser->isAdmin() ? 'Ketua Tim' : 'PIC Pendamping') : 'Viewer (Hanya Lihat)';
        return back()->with('success', "Akun PIC {$targetUser->name} berhasil ditautkan sebagai {$roleLabel}.");
    }

    /**
     * Putuskan akses akun PIC dari tim kolaborasi LPK.
     */
    public function destroy(Lpk $lpk, User $user, Request $request): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $lpk->canManage($currentUser)) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola tim PIC pada LPK ini.');
        }

        if (! $lpk->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', "PIC {$user->name} tidak terdaftar dalam tim kolaborasi LPK ini.");
        }

        $lpk->members()->detach($user->id);

        return back()->with('success', "Akses PIC {$user->name} berhasil diputuskan dari LPK ini.");
    }
}
