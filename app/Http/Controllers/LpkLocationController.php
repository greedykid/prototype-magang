<?php

namespace App\Http\Controllers;

use App\Models\Lpk;
use App\Models\LpkLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LpkLocationController extends Controller
{
    /**
     * Tambahkan lokasi / cabang baru untuk LPK.
     */
    public function store(Request $request, Lpk $lpk): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $lpk->canManage($currentUser)) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola data lokasi pada LPK ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', 'string', Rule::in(array_keys(LpkLocation::LOCATION_TYPES))],
            'address' => ['required', 'string', 'max:1000'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:100'],
            'scope_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Nama lokasi/cabang wajib diisi.',
            'location_type.required' => 'Pilih jenis fasilitas lokasi.',
            'location_type.in' => 'Jenis fasilitas yang dipilih tidak valid.',
            'address.required' => 'Alamat lengkap fasilitas wajib diisi.',
            'contact_email.email' => 'Format email narahubung tidak valid.',
        ]);

        $isMain = $validated['location_type'] === LpkLocation::TYPE_HEAD_OFFICE;

        // Jika ini adalah lokasi pertama, otomatis jadikan lokasi utama
        if ($lpk->locations()->count() === 0) {
            $isMain = true;
            $validated['location_type'] = LpkLocation::TYPE_HEAD_OFFICE;
        }

        DB::transaction(function () use ($lpk, $validated, $isMain) {
            if ($isMain) {
                $lpk->locations()->update([
                    'is_main' => false,
                    'location_type' => LpkLocation::TYPE_BRANCH_LAB,
                ]);
            }

            $lpk->locations()->create(array_merge($validated, [
                'is_main' => $isMain,
            ]));
        });

        return back()->with('success', "Lokasi fasilitas \"{$validated['name']}\" berhasil ditambahkan.");
    }

    /**
     * Perbarui data lokasi / cabang LPK.
     */
    public function update(Request $request, Lpk $lpk, LpkLocation $location): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $lpk->canManage($currentUser)) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola data lokasi pada LPK ini.');
        }

        if ((int) $location->lpk_id !== (int) $lpk->id) {
            abort(404, 'Lokasi tidak ditemukan pada LPK ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', 'string', Rule::in(array_keys(LpkLocation::LOCATION_TYPES))],
            'address' => ['required', 'string', 'max:1000'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:100'],
            'scope_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Nama lokasi/cabang wajib diisi.',
            'location_type.required' => 'Pilih jenis fasilitas lokasi.',
            'location_type.in' => 'Jenis fasilitas yang dipilih tidak valid.',
            'address.required' => 'Alamat lengkap fasilitas wajib diisi.',
            'contact_email.email' => 'Format email narahubung tidak valid.',
        ]);

        $isMain = $validated['location_type'] === LpkLocation::TYPE_HEAD_OFFICE;

        // Pastikan selalu ada minimal 1 lokasi utama jika ini satu-satunya lokasi
        if (! $isMain && $location->is_main && ! $lpk->locations()->where('id', '!=', $location->id)->where('is_main', true)->exists()) {
            $isMain = true;
            $validated['location_type'] = LpkLocation::TYPE_HEAD_OFFICE;
        }

        DB::transaction(function () use ($lpk, $location, $validated, $isMain) {
            if ($isMain && ! $location->is_main) {
                // Alihkan lokasi utama lama menjadi cabang
                $lpk->locations()->where('id', '!=', $location->id)->update([
                    'is_main' => false,
                    'location_type' => LpkLocation::TYPE_BRANCH_LAB,
                ]);
            }

            $location->update(array_merge($validated, [
                'is_main' => $isMain,
            ]));
        });

        return back()->with('success', "Informasi lokasi \"{$location->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus lokasi / cabang LPK.
     */
    public function destroy(Request $request, Lpk $lpk, LpkLocation $location): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $lpk->canManage($currentUser)) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus data lokasi pada LPK ini.');
        }

        if ((int) $location->lpk_id !== (int) $lpk->id) {
            abort(404, 'Lokasi tidak ditemukan pada LPK ini.');
        }

        if ($lpk->locations()->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya lokasi yang terdaftar pada LPK ini.');
        }

        if ($location->assessments()->exists()) {
            return back()->with('error', "Lokasi \"{$location->name}\" memiliki riwayat asesmen terkait. Silakan alihkan data asesmen terlebih dahulu.");
        }

        $locName = $location->name;
        $wasMain = $location->is_main;

        DB::transaction(function () use ($lpk, $location, $wasMain) {
            $location->delete();

            // Jika lokasi utama yang dihapus, alihkan status utama ke lokasi tersisa pertama
            if ($wasMain) {
                $next = $lpk->locations()->first();
                if ($next) {
                    $next->update(['is_main' => true]);
                }
            }
        });

        return back()->with('success', "Lokasi fasilitas \"{$locName}\" berhasil dihapus.");
    }
}
