<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateAssessmentTpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED,SUSPENDED'],
            'tp_status' => ['required', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'tp_due_date' => ['nullable', 'date'],
            'tp_has_extension' => ['nullable', 'boolean'],
            'tp_extension_months' => ['nullable', 'integer', 'min:0', 'max:1'],
            'tp_extension_letter_no' => ['nullable', 'string', 'max:255'],
            'tp_extension_date' => ['nullable', 'date'],
            'tp_extension_notes' => ['nullable', 'string'],
            'tp_satisfied_at' => ['nullable', 'date'],
            'tp_notes' => ['nullable', 'string'],
            'report_date' => ['nullable', 'date'],
            'eha_date' => ['nullable', 'date'],
            'eha_status' => ['nullable', 'string', 'in:BELUM_EHA,DIREKOMENDASIKAN,PERLU_VERIFIKASI,CATATAN_KHUSUS'],
            'eha_notes' => ['nullable', 'string'],
            'sk_number' => ['nullable', 'string', 'max:150'],
            'sk_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasExt = $this->boolean('tp_has_extension') || ! empty($this->input('tp_extension_letter_no'));
            if ($hasExt && $this->input('tp_status') === Assessment::TP_STATUS_NONE) {
                $validator->errors()->add(
                    'tp_has_extension',
                    'Perpanjangan waktu tindakan perbaikan (+1 bulan) hanya dapat diajukan jika terdapat upaya perbaikan (status Penyusunan Perbaikan atau Verifikasi Tim Asesor), bukan Nihil / Tanpa Tindakan Perbaikan.'
                );
            }
        });
    }

    public function normalizedData(Assessment $assessment): array
    {
        $validated = $this->validated();
        $validated['tp_has_extension'] = $this->boolean('tp_has_extension') || ! empty($validated['tp_extension_letter_no']);
        if ($validated['tp_has_extension'] && empty($validated['tp_extension_months'])) {
            $validated['tp_extension_months'] = 1;
        }

        if (empty($validated['status'])) {
            $tpDueDate = ! empty($validated['tp_due_date']) ? Carbon::parse($validated['tp_due_date']) : $assessment->tp_due_date;
            $tpHasExtension = (bool) ($validated['tp_has_extension'] ?? $assessment->tp_has_extension);
            $tpExtensionMonths = (int) ($validated['tp_extension_months'] ?? $assessment->tp_extension_months);
            $tpSatisfiedAt = ! empty($validated['tp_satisfied_at']) ? Carbon::parse($validated['tp_satisfied_at']) : $assessment->tp_satisfied_at;

            $validated['status'] = Assessment::determineStatusFromDates(
                $assessment->start_at,
                $assessment->end_at,
                $assessment->status,
                $validated['tp_status'] ?? null,
                $validated['sk_number'] ?? null,
                ! empty($validated['report_date']) ? Carbon::parse($validated['report_date']) : $assessment->report_date,
                ! empty($validated['eha_date']) ? Carbon::parse($validated['eha_date']) : $assessment->eha_date,
                $tpDueDate,
                $tpHasExtension,
                $tpExtensionMonths,
                $tpSatisfiedAt,
                $assessment->assessment_type
            );
        }

        return $validated;
    }
}
