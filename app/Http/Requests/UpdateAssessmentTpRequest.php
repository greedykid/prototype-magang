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

    protected function prepareForValidation(): void
    {
        $hasExt = $this->has('tp_has_extension')
            ? $this->boolean('tp_has_extension')
            : ! empty($this->input('tp_extension_letter_no'));

        if (! $hasExt) {
            $this->merge([
                'tp_has_extension' => false,
                'tp_extension_letter_no' => null,
                'tp_extension_date' => null,
                'tp_extension_notes' => null,
                'tp_extension_months' => 0,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'unified_status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,IN_PROGRESS_TP,UNDER_VERIFICATION,SUSPENDED,COMPLETED,CANCELLED'],
            'status' => ['nullable', 'string', 'in:PLANNED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED,SUSPENDED'],
            'tp_status' => ['required_without_all:unified_status,tp_satisfied_at,sk_number', 'nullable', 'string', 'in:NONE,IN_PROGRESS,UNDER_VERIFICATION,SATISFIED'],
            'end_at' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    $startAt = $this->input('start_at') ?: $this->route('assessment')?->start_at;
                    if ($value && $startAt && \Illuminate\Support\Carbon::parse($value)->lt(\Illuminate\Support\Carbon::parse($startAt))) {
                        $fail('Tanggal selesai pelaksanaan harus sama atau setelah tanggal mulai.');
                    }
                },
            ],
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
            'tp_satisfied_at' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $assessment = $this->route('assessment');
                    $satisfiedAt = rescue(fn () => Carbon::parse($value)->startOfDay(), null, false);
                    if (! $satisfiedAt) {
                        return;
                    }

                    $hasExt = $this->has('tp_has_extension')
                        ? $this->boolean('tp_has_extension')
                        : ! empty($this->input('tp_extension_letter_no'));
                    $extMonths = $hasExt ? max(1, (int) ($this->input('tp_extension_months', 1) ?: 1)) : 0;
                    $rawDueDate = $this->input('tp_due_date')
                        ? rescue(fn () => Carbon::parse($this->input('tp_due_date'))->startOfDay(), null, false)
                        : $assessment?->tp_due_date?->startOfDay();
                    $defaultBase = $assessment ? app(\App\Services\AssessmentTpService::class)->calculateDefaultTpDueDate($assessment)?->startOfDay() : null;

                    $effectiveDue = app(\App\Services\AssessmentTpService::class)->resolveEffectiveTpDueDate($rawDueDate, $hasExt, $extMonths, $defaultBase);

                    if ($effectiveDue && $satisfiedAt->gt($effectiveDue)) {
                        $fail("Tanggal memenuhi ({$satisfiedAt->format('d/m/Y')}) tidak boleh melewati tenggat waktu tindakan perbaikan ({$effectiveDue->format('d/m/Y')}) sesuai Klausul 2.11.12 KAN U-01 Rev.3. Melewati batas waktu perbaikan berakibat pembekuan akreditasi.");
                    }
                },
            ],
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
            $hasExt = $this->has('tp_has_extension')
                ? $this->boolean('tp_has_extension')
                : ! empty($this->input('tp_extension_letter_no'));
            $isTpNone = $this->input('tp_status') === Assessment::TP_STATUS_NONE
                || in_array($this->input('unified_status'), ['SCHEDULED', 'PLANNED', 'CANCELLED'], true);

            if ($hasExt && $isTpNone) {
                $validator->errors()->add(
                    'tp_has_extension',
                    'Perpanjangan waktu tindakan perbaikan (+1 bulan) hanya dapat diajukan jika terdapat upaya perbaikan (status Penyusunan Perbaikan atau Verifikasi Tim Asesor), bukan Nihil / Tanpa Tindakan Perbaikan.'
                );
            }

            $isSatisfied = ($this->input('tp_status') === Assessment::TP_STATUS_SATISFIED)
                || ($this->input('unified_status') === 'COMPLETED');

            if ($isSatisfied) {
                $assessment = $this->route('assessment');
                $satisfiedVal = $this->input('tp_satisfied_at') ?: $assessment?->tp_satisfied_at?->toDateString();
                $satisfiedAt = $satisfiedVal
                    ? rescue(fn () => Carbon::parse($satisfiedVal)->startOfDay(), null, false)
                    : now()->startOfDay();

                $extMonths = $hasExt ? max(1, (int) ($this->input('tp_extension_months', 1) ?: 1)) : 0;
                $rawDueDate = $this->input('tp_due_date')
                    ? rescue(fn () => Carbon::parse($this->input('tp_due_date'))->startOfDay(), null, false)
                    : $assessment?->tp_due_date?->startOfDay();
                $defaultBase = $assessment ? app(\App\Services\AssessmentTpService::class)->calculateDefaultTpDueDate($assessment)?->startOfDay() : null;
                $effectiveDue = app(\App\Services\AssessmentTpService::class)->resolveEffectiveTpDueDate($rawDueDate, $hasExt, $extMonths, $defaultBase);

                if ($effectiveDue && $satisfiedAt && $satisfiedAt->gt($effectiveDue)) {
                    $validator->errors()->add(
                        'tp_satisfied_at',
                        "Tindakan perbaikan tidak dapat dinyatakan memenuhi karena tanggal memenuhi ({$satisfiedAt->format('d/m/Y')}) telah melewati tenggat waktu perbaikan ({$effectiveDue->format('d/m/Y')}) sesuai Klausul 2.11.12 KAN U-01 Rev.3. Melewati batas waktu perbaikan berakibat pembekuan akreditasi."
                    );
                }
            }
        });
    }

    public function normalizedData(Assessment $assessment): array
    {
        $validated = $this->validated();

        if (! empty($validated['end_at'])) {
            $validated['end_at'] = Carbon::parse($validated['end_at'])->endOfDay();
        }

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

        $hasExplicitTpStatus = $this->has('tp_status') && $this->input('tp_status') !== null && $this->input('tp_status') !== '';

        if (! empty($validated['unified_status'])) {
            $uStatus = $validated['unified_status'];
            switch ($uStatus) {
                case 'COMPLETED':
                    $validated['status'] = 'COMPLETED';
                    if (! $hasExplicitTpStatus) {
                        $validated['tp_status'] = Assessment::TP_STATUS_SATISFIED;
                        if (empty($validated['tp_satisfied_at']) && empty($assessment->tp_satisfied_at)) {
                            $validated['tp_satisfied_at'] = now()->toDateString();
                        }
                    }
                    break;
                case 'SUSPENDED':
                    $validated['status'] = 'SUSPENDED';
                    if (! $hasExplicitTpStatus) {
                        $validated['tp_status'] = Assessment::TP_STATUS_IN_PROGRESS;
                    }
                    break;
                case 'UNDER_VERIFICATION':
                    $validated['status'] = 'IN_PROGRESS';
                    if (! $hasExplicitTpStatus) {
                        $validated['tp_status'] = Assessment::TP_STATUS_UNDER_VERIFICATION;
                    }
                    break;
                case 'IN_PROGRESS':
                case 'IN_PROGRESS_TP':
                    $validated['status'] = 'IN_PROGRESS';
                    if (! $hasExplicitTpStatus) {
                        $validated['tp_status'] = Assessment::TP_STATUS_IN_PROGRESS;
                    }
                    break;
                case 'CANCELLED':
                    $validated['status'] = 'CANCELLED';
                    if (! $hasExplicitTpStatus) {
                        $validated['tp_status'] = Assessment::TP_STATUS_NONE;
                    }
                    break;
                case 'PLANNED':
                    $validated['status'] = 'PLANNED';
                    if (! $hasExplicitTpStatus) {
                        $validated['tp_status'] = Assessment::TP_STATUS_NONE;
                    }
                    break;
                default:
                    $validated['status'] = 'SCHEDULED';
                    if (! $hasExplicitTpStatus) {
                        $validated['tp_status'] = Assessment::TP_STATUS_NONE;
                    }
                    break;
            }
            unset($validated['unified_status']);
        }

        if (! empty($validated['sk_number']) && ! empty($validated['sk_date'])) {
            if ($assessment->status !== 'CANCELLED') {
                $validated['status'] = 'COMPLETED';
                if (! $hasExplicitTpStatus) {
                    $validated['tp_status'] = Assessment::TP_STATUS_SATISFIED;
                    if (empty($validated['tp_satisfied_at']) && empty($assessment->tp_satisfied_at)) {
                        $validated['tp_satisfied_at'] = Carbon::parse($validated['sk_date'])->toDateString();
                    }
                }
            }
        }

        if (! empty($validated['tp_satisfied_at'])) {
            $validated['tp_status'] = Assessment::TP_STATUS_SATISFIED;
        } else {
            if ($hasExplicitTpStatus) {
                if ($validated['tp_status'] === Assessment::TP_STATUS_SATISFIED) {
                    if (empty($validated['tp_satisfied_at']) && empty($assessment->tp_satisfied_at)) {
                        $validated['tp_satisfied_at'] = now()->toDateString();
                    }
                } else {
                    $validated['tp_satisfied_at'] = null;
                }
            }
        }

        if (! empty($validated['eha_date']) && (empty($validated['eha_status']) || $validated['eha_status'] === Assessment::EHA_STATUS_BELUM)) {
            $parsedEha = rescue(fn () => Carbon::parse($validated['eha_date'])->endOfDay(), null, false);
            if ($parsedEha && $parsedEha->lte(now())) {
                $validated['eha_status'] = Assessment::EHA_STATUS_DIREKOMENDASIKAN;
            }
        }

        if (empty($validated['status'])) {
            $tpDueDate = ! empty($validated['tp_due_date']) ? Carbon::parse($validated['tp_due_date']) : $assessment->tp_due_date;
            $tpHasExtension = (bool) ($validated['tp_has_extension'] ?? $assessment->tp_has_extension);
            $tpExtensionMonths = (int) ($validated['tp_extension_months'] ?? $assessment->tp_extension_months);
            $tpExtensionDate = ! empty($validated['tp_extension_date']) ? Carbon::parse($validated['tp_extension_date']) : $assessment->tp_extension_date;
            $tpSatisfiedAt = array_key_exists('tp_satisfied_at', $validated)
                ? (! empty($validated['tp_satisfied_at']) ? Carbon::parse($validated['tp_satisfied_at']) : null)
                : $assessment->tp_satisfied_at;

            $validated['status'] = Assessment::determineStatusFromDates(
                $assessment->start_at,
                $assessment->end_at,
                $assessment->status,
                $validated['tp_status'] ?? $assessment->tp_status,
                $validated['sk_number'] ?? $assessment->sk_number,
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
