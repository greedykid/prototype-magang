<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna akun SIMASADI dengan fitur filter dan pencarian.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = User::query()
            ->withCount(['lpks', 'memberLpks'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($role, [User::ROLE_ADMIN, User::ROLE_PIC], true), function ($q) use ($role) {
                $q->where('role', $role);
            })
            ->latest('id');

        $users = $query->paginate($perPage)->withQueryString();

        if ($request->ajax() && $request->hasHeader('X-Partial-Content')) {
            return view('users.partials.table-content', compact('users', 'search', 'role', 'perPage'));
        }

        $totalCount = User::count();
        $adminCount = User::where('role', User::ROLE_ADMIN)->count();
        $picCount = User::where('role', User::ROLE_PIC)->count();

        return view('users.index', compact(
            'users',
            'search',
            'role',
            'perPage',
            'totalCount',
            'adminCount',
            'picCount'
        ));
    }

    /**
     * Tampilkan rincian profil anggota beserta seluruh laboratorium yang ditanganinya.
     */
    public function show(User $user, Request $request): View
    {
        // 1. LPK Utama (Lead PIC / Pemilik Utama)
        $leadLpks = $user->lpks()
            ->with(['assessments'])
            ->orderBy('name')
            ->get();

        // 2. LPK Kolaborasi (Anggota / Viewer LPK)
        $memberLpks = $user->memberLpks()
            ->with(['assessments'])
            ->orderBy('name')
            ->get();

        // 3. LPK dari Tautan Akun (Account-level Viewer)
        $linkedOwners = $user->linkedOwners()->withCount('lpks')->get();
        $linkedOwnerLpks = \App\Models\Lpk::whereIn('pic_id', $linkedOwners->pluck('id'))
            ->with(['assessments'])
            ->orderBy('name')
            ->get();

        $linkedViewers = $user->linkedViewers()->withCount('lpks')->get();

        // 4. Gabungan unik LPK
        $allLpks = $leadLpks->concat($memberLpks)->concat($linkedOwnerLpks)->unique('id');

        // Statistik ringkas
        $totalLead = $leadLpks->count();
        $totalViewer = max(0, $allLpks->count() - $totalLead);
        $totalLpks = $allLpks->count();

        return view('users.show', compact(
            'user',
            'leadLpks',
            'memberLpks',
            'linkedOwnerLpks',
            'linkedOwners',
            'linkedViewers',
            'allLpks',
            'totalLead',
            'totalViewer',
            'totalLpks'
        ));
    }

    /**
     * Tampilkan formulir penambahan akun pengguna baru.
     */
    public function create(): View
    {
        return view('users.form', [
            'user' => new User(),
            'formTitle' => 'Tambah Anggota Baru',
        ]);
    }

    /**
     * Simpan pengguna baru ke basis data.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        Log::info('User account created', [
            'new_user_id' => $newUser->id,
            'new_user_email' => $newUser->email,
            'role' => $newUser->role,
            'created_by' => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna baru berhasil ditambahkan.');
    }

    /**
     * Tampilkan formulir edit akun pengguna.
     */
    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'formTitle' => 'Edit Pengguna: ' . $user->name,
        ]);
    }

    /**
     * Perbarui data akun pengguna di basis data.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        // Proteksi keamanan: Ketua Tim yang sedang login tidak boleh mendegradasi perannya sendiri
        if ($request->user()->id === $user->id && $validated['role'] !== User::ROLE_ADMIN) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak dapat menurunkan peran akun Anda sendiri dari Ketua Tim.');
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        Log::info('User account updated', [
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'role' => $user->role,
            'updated_by' => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Data akun ' . $user->name . ' berhasil diperbarui.');
    }

    /**
     * Hapus akun pengguna dari basis data.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Proteksi keamanan: Pengguna tidak boleh menghapus akunnya sendiri yang sedang aktif
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login.');
        }

        $userId = $user->id;
        $userEmail = $user->email;
        $userName = $user->name;
        $user->delete();

        Log::info('User account deleted', [
            'deleted_user_id' => $userId,
            'deleted_user_email' => $userEmail,
            'deleted_user_name' => $userName,
            'deleted_by' => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna ' . $userName . ' berhasil dihapus.');
    }
}
