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
            'unified_status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS_TP,UNDER_VERIFICATION,SUSPENDED,COMPLETED,CANCELLED'],
            'status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED,SUSPENDED'],
            'tp_status' => ['required_without:unified_status', 'nullable', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'tp_due_date' => ['nullable', 'date'],
            'tp_has_extension' => ['nullable', 'boolean'],
            'tp_extension_months' => ['nullable', 'integer', 'min:0', 'max:1'],
            'tp_extension_letter_no' => ['nullable', 'string', 'max:80'],
            'tp_extension_date' => ['nullable', 'date'],
            'tp_extension_notes' => ['nullable', 'string'],
            'tp_satisfied_at' => ['nullable', 'date'],
            'tp_notes' => ['nullable', 'string'],
            'report_date' => ['nullable', 'date'],
            'eha_date' => ['nullable', 'date'],
            'eha_status' => ['nullable', 'string', 'in:BELUM_EHA,DIREKOMENDASIKAN,PERLU_VERIFIKASI,CATATAN_KHUSUS'],
            'eha_notes' => ['nullable', 'string'],
            'sk_number' => ['nullable', 'string', 'max:80'],
            'sk_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasExt = $this->boolean('tp_has_extension') || ! empty($this->input('tp_extension_letter_no'));
            $isTpNone = $this->input('tp_status') === Assessment::TP_STATUS_NONE
                || in_array($this->input('unified_status'), ['SCHEDULED', 'PLANNED', 'CANCELLED'], true);

            if ($hasExt && $isTpNone) {
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

        if (! empty($validated['unified_status'])) {
            $uStatus = $validated['unified_status'];
            switch ($uStatus) {
                case 'COMPLETED':
                    $validated['status'] = 'COMPLETED';
                    $validated['tp_status'] = Assessment::TP_STATUS_SATISFIED;
                    if (empty($validated['tp_satisfied_at']) && empty($assessment->tp_satisfied_at)) {
                        $validated['tp_satisfied_at'] = now()->toDateString();
                    }
                    break;
                case 'SUSPENDED':
                    $validated['status'] = 'SUSPENDED';
                    $validated['tp_status'] = Assessment::TP_STATUS_IN_PROGRESS;
                    break;
                case 'UNDER_VERIFICATION':
                    $validated['status'] = 'IN_PROGRESS';
                    $validated['tp_status'] = Assessment::TP_STATUS_UNDER_VERIFICATION;
                    break;
                case 'IN_PROGRESS_TP':
                    $validated['status'] = 'IN_PROGRESS';
                    $validated['tp_status'] = Assessment::TP_STATUS_IN_PROGRESS;
                    break;
                case 'CANCELLED':
                    $validated['status'] = 'CANCELLED';
                    $validated['tp_status'] = Assessment::TP_STATUS_NONE;
                    break;
                case 'PLANNED':
                    $validated['status'] = 'PLANNED';
                    $validated['tp_status'] = Assessment::TP_STATUS_NONE;
                    break;
                default:
                    $validated['status'] = 'SCHEDULED';
                    $validated['tp_status'] = Assessment::TP_STATUS_NONE;
                    break;
            }
            unset($validated['unified_status']);
        } elseif (empty($validated['status'])) {
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
