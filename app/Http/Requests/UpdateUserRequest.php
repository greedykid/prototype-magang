<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $targetUser = $this->route('user');
        $userId = $targetUser instanceof User ? $targetUser->id : $targetUser;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => [
                'required',
                'string',
                'max:20',
                Rule::in([User::ROLE_ADMIN, User::ROLE_PIC]),
                function ($attribute, $value, $fail) use ($userId) {
                    if ($value === User::ROLE_ADMIN) {
                        $adminExists = User::where('role', User::ROLE_ADMIN)
                            ->where('id', '!=', $userId)
                            ->exists();
                        if ($adminExists) {
                            $fail('Hanya diperbolehkan memiliki satu akun Ketua Tim dalam sistem.');
                        }
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Alamat email ini sudah digunakan oleh akun lain.',
            'password.min' => 'Kata sandi baru minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }
}
