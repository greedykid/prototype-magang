<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('start_date')) {
            $startTime = $this->filled('start_time') ? (string) $this->input('start_time') : '09:00';
            $endTime = $this->filled('end_time') ? (string) $this->input('end_time') : '17:00';
            $endDate = $this->filled('end_date') ? (string) $this->input('end_date') : (string) $this->input('start_date');

            $this->merge([
                'start_at' => trim($this->input('start_date').' '.$startTime),
                'end_at' => trim($endDate.' '.$endTime),
            ]);
        }
    }

    public function rules(): array
    {
        $usesSplitDateFields = $this->filled('start_date');

        return [
            'lpk_id' => ['required', 'exists:lpks,id'],
            'title' => ['required', 'string', 'max:150'],
            'assessment_type' => ['required', 'string', 'max:60'],
            'start_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'start_time' => ['nullable', 'string', 'max:10'],
            'end_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'submission_due_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED,SUSPENDED'],
            'lead_assessor' => ['nullable', 'string', 'max:100'],
            'assessment_team' => ['nullable', 'string', 'max:1000'],
            'report_date' => ['nullable', 'date'],
            'eha_date' => ['nullable', 'date'],
            'eha_status' => ['nullable', 'string', 'in:BELUM_EHA,DIREKOMENDASIKAN,PERLU_VERIFIKASI,CATATAN_KHUSUS'],
            'eha_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tp_status' => ['nullable', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'tp_due_date' => ['nullable', 'date'],
            'tp_has_extension' => ['nullable', 'boolean'],
            'tp_extension_months' => ['nullable', 'integer', 'min:0', 'max:1'],
            'tp_extension_letter_no' => ['nullable', 'string', 'max:80'],
            'tp_extension_date' => ['nullable', 'date'],
            'tp_extension_notes' => ['nullable', 'string'],
            'tp_satisfied_at' => ['nullable', 'date'],
            'tp_notes' => ['nullable', 'string'],
            'sk_number' => ['nullable', 'string', 'max:80'],
            'sk_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'lpk_id.required' => 'LPK wajib dipilih.',
            'lpk_id.exists' => 'Data LPK yang dipilih tidak ditemukan dalam sistem.',
            'title.required' => 'Judul kegiatan asesmen wajib diisi.',
            'assessment_type.required' => 'Jenis asesmen wajib dipilih.',
            'assessment_type.max' => 'Jenis asesmen tidak boleh lebih dari :max karakter.',
            'start_at.required' => 'Waktu mulai pelaksanaan wajib diisi.',
            'end_at.required' => 'Waktu selesai pelaksanaan wajib diisi.',
            'end_at.after' => 'Waktu selesai pelaksanaan harus setelah waktu mulai pelaksanaan.',
            'start_date.required' => 'Tanggal mulai pelaksanaan wajib diisi.',
            'end_date.required' => 'Tanggal selesai pelaksanaan wajib diisi.',
        ];
    }

    public function normalizedPayload(?Assessment $assessment = null): array
    {
        $data = $this->validated();

        unset($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);

        if (! empty($data['assessment_team'])) {
            $data['lead_assessor'] = $data['assessment_team'];
        } elseif (! empty($data['lead_assessor'])) {
            $data['assessment_team'] = $data['lead_assessor'];
        }

        $startAt = ! empty($data['start_at']) ? Carbon::parse($data['start_at']) : null;
        $endAt = ! empty($data['end_at']) ? Carbon::parse($data['end_at']) : null;
        $tpDueDate = ! empty($data['tp_due_date']) ? Carbon::parse($data['tp_due_date']) : $assessment?->tp_due_date;
        $tpHasExtension = (bool) ($data['tp_has_extension'] ?? $assessment?->tp_has_extension ?? false);
        $tpExtensionMonths = (int) ($data['tp_extension_months'] ?? $assessment?->tp_extension_months ?? 0);
        $tpSatisfiedAt = ! empty($data['tp_satisfied_at']) ? Carbon::parse($data['tp_satisfied_at']) : $assessment?->tp_satisfied_at;

        $data['status'] = Assessment::determineStatusFromDates(
            $startAt,
            $endAt,
            $data['status'] ?? $assessment?->status,
            $data['tp_status'] ?? $assessment?->tp_status,
            $data['sk_number'] ?? $assessment?->sk_number,
            ! empty($data['report_date']) ? Carbon::parse($data['report_date']) : $assessment?->report_date,
            ! empty($data['eha_date']) ? Carbon::parse($data['eha_date']) : $assessment?->eha_date,
            $tpDueDate,
            $tpHasExtension,
            $tpExtensionMonths,
            $tpSatisfiedAt,
            $data['assessment_type'] ?? $assessment?->assessment_type
        );

        return $data;
    }
}
