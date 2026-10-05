<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLpkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $lpk = $this->route('lpk');

        if ($user && $user->isPic() && $lpk && ! $lpk->isManagedBy($user)) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $lpk = $this->route('lpk');
        $id = $lpk?->id ?? 'NULL';

        return [
            'no_reg' => ['nullable', 'string', 'max:30', 'unique:lpks,no_reg,'.$id],
            'accreditation_number' => ['nullable', 'string', 'max:50'],
            'accreditation_type' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50', 'unique:lpks,registration_number,'.$id],
            'name' => ['required', 'string', 'max:150'],
            'scope' => ['nullable', 'string', 'max:50000'],
            'certificate_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,SUSPENDED'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'expired_at' => ['nullable', 'date'],
            'drive_url' => ['nullable', 'url', 'max:1000'],
            'pic_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function normalizedData(): array
    {
        $data = $this->validated();

        if (empty($data['no_reg']) && ! empty($data['registration_number']) && preg_match('/^\d+$/', (string) $data['registration_number'])) {
            $data['no_reg'] = $data['registration_number'];
        }

        if (empty($data['accreditation_number']) && ! empty($data['registration_number'])) {
            $data['accreditation_number'] = $data['registration_number'];
        }

        if (empty($data['registration_number'])) {
            $data['registration_number'] = $data['accreditation_number'] ?: ($data['no_reg'] ?? 'LPK-' . time());
        }

        if (empty($data['accreditation_type'])) {
            $data['accreditation_type'] = 'Laboratorium Penguji';
        }

        if (! empty($data['certificate_date']) && empty($data['expired_at'])) {
            $data['expired_at'] = Carbon::parse($data['certificate_date'])->addYears(5)->toDateString();
        } elseif (empty($data['certificate_date']) && ! empty($data['expired_at'])) {
            $data['certificate_date'] = Carbon::parse($data['expired_at'])->subYears(5)->toDateString();
        }

        return $data;
    }
}
