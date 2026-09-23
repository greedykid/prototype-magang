<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
     * Tampilkan formulir penambahan akun pengguna baru.
     */
    public function create(): View
    {
        return view('users.form', [
            'user' => new User(),
            'formTitle' => 'Tambah Pengguna Baru',
        ]);
    }

    /**
     * Simpan pengguna baru ke basis data.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_PIC])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Alamat email ini sudah terdaftar pada pengguna lain.',
            'password.min' => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
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
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_PIC])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Alamat email ini sudah digunakan oleh akun lain.',
            'password.min' => 'Kata sandi baru minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        // Proteksi keamanan: Admin yang sedang login tidak boleh mendegradasi perannya sendiri
        if ($request->user()->id === $user->id && $validated['role'] !== User::ROLE_ADMIN) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak dapat menurunkan peran akun Anda sendiri dari Administrator.');
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

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

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna ' . $userName . ' berhasil dihapus.');
    }
}
