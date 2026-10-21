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
            'unified_status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,IN_PROGRESS_TP,UNDER_VERIFICATION,SUSPENDED,COMPLETED,CANCELLED'],
            'status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED,SUSPENDED'],
            'tp_status' => ['required_without:unified_status', 'nullable', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'tp_due_date' => ['nullable', 'date'],
            'tp_has_extension' => ['nullable', 'boolean'],
            'tp_extension_months' => ['nullable', 'integer', 'min:0', 'max:1'],
            'tp_extension_letter_no' => [
                'required_if_accepted:tp_has_extension',
                'required_with:tp_extension_date',
                'nullable',
                'string',
                'max:80',
            ],
            'tp_extension_date' => [
                'required_if_accepted:tp_has_extension',
                'required_with:tp_extension_letter_no',
                'nullable',
                'date',
            ],
            'tp_extension_notes' => ['nullable', 'string'],
            'tp_satisfied_at' => ['nullable', 'date'],
            'tp_notes' => ['nullable', 'string'],
            'report_date' => ['nullable', 'date'],
            'eha_date' => ['nullable', 'date'],
            'eha_status' => ['nullable', 'string', 'in:BELUM_EHA,DIREKOMENDASIKAN,PERLU_VERIFIKASI,CATATAN_KHUSUS'],
            'eha_notes' => ['nullable', 'string'],
            'sk_number' => ['nullable', 'string', 'max:80', 'required_with:sk_date'],
            'sk_date' => ['nullable', 'date', 'required_with:sk_number'],
        ];
    }

    public function messages(): array
    {
        return [
            'tp_extension_letter_no.required_if_accepted' => 'Nomor surat resmi LPK wajib diisi saat mengajukan perpanjangan waktu tindakan perbaikan.',
            'tp_extension_letter_no.required_with' => 'Nomor surat resmi LPK wajib diisi jika tanggal surat perpanjangan diisi.',
            'tp_extension_date.required_if_accepted' => 'Tanggal surat resmi LPK wajib diisi saat mengajukan perpanjangan waktu tindakan perbaikan.',
            'tp_extension_date.required_with' => 'Tanggal surat resmi LPK wajib diisi jika nomor surat perpanjangan diisi.',
            'sk_number.required_with' => 'Nomor SK KAN wajib diisi jika tanggal SK diisi.',
            'sk_date.required_with' => 'Tanggal SK KAN wajib diisi jika nomor SK diisi.',
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
        $hasExt = $this->boolean('tp_has_extension');
        $validated['tp_has_extension'] = $hasExt;
        if ($hasExt) {
            if (empty($validated['tp_extension_months'])) {
                $validated['tp_extension_months'] = 1;
            }
        } else {
            $validated['tp_extension_months'] = 0;
            $validated['tp_extension_letter_no'] = null;
            $validated['tp_extension_date'] = null;
            $validated['tp_extension_notes'] = null;
        }

        if (! empty($validated['unified_status'])) {
            $uStatus = $validated['unified_status'];
            switch ($uStatus) {
                case 'COMPLETED':
                    $validated['tp_status'] = Assessment::TP_STATUS_SATISFIED;
                    if (empty($validated['tp_satisfied_at']) && empty($assessment->tp_satisfied_at)) {
                        $validated['tp_satisfied_at'] = now()->toDateString();
                    }
                    $effectiveEhaStatus = $validated['eha_status'] ?? $assessment->eha_status;
                    $effectiveEhaDate = ! empty($validated['eha_date']) ? $validated['eha_date'] : $assessment->eha_date;
                    $hasEha = in_array($effectiveEhaStatus, [Assessment::EHA_STATUS_DIREKOMENDASIKAN, 'DIREKOMENDASIKAN', 'SUDAH_EHA'], true)
                        || (! empty($effectiveEhaDate) && ! in_array($effectiveEhaStatus, [Assessment::EHA_STATUS_BELUM, Assessment::EHA_STATUS_PERLU_VERIFIKASI, 'BELUM_EHA', 'PERLU_VERIFIKASI'], true));
                    $hasSk = ! empty($validated['sk_number']) || ! empty($assessment->sk_number);
                    $validated['status'] = ($hasEha || $hasSk) ? 'COMPLETED' : 'IN_PROGRESS';
                    break;
                case 'SUSPENDED':
                    $validated['status'] = 'SUSPENDED';
                    $validated['tp_status'] = Assessment::TP_STATUS_IN_PROGRESS;
                    break;
                case 'UNDER_VERIFICATION':
                    $validated['status'] = 'IN_PROGRESS';
                    $validated['tp_status'] = Assessment::TP_STATUS_UNDER_VERIFICATION;
                    break;
                case 'IN_PROGRESS':
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
            $tpExtensionDate = ! empty($validated['tp_extension_date']) ? Carbon::parse($validated['tp_extension_date']) : $assessment->tp_extension_date;
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
                $assessment->assessment_type,
                $tpExtensionDate,
                $validated['eha_status'] ?? $assessment->eha_status
            );
        }

        return $validated;
    }
}
