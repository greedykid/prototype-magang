<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $assessment = $this->route('assessment');

        if ($user && $user->isPic() && $assessment && ! $assessment->isManagedBy($user)) {
            return false;
        }

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
        $usesSplitDateFields = $this->filled('start_date');

        return [
            'lpk_id' => ['required', 'exists:lpks,id'],
            'lpk_location_id' => [
                'nullable',
                'integer',
                Rule::exists('lpk_locations', 'id')->where(function ($q) {
                    $lpkId = $this->input('lpk_id') ?: $this->route('assessment')?->lpk_id;
                    $q->where('lpk_id', (int) $lpkId);
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'assessment_type' => ['required', 'string', 'max:60'],
            'start_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'start_time' => ['nullable', 'string', 'max:10'],
            'end_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'submission_due_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
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

                    $hasExt = $this->boolean('tp_has_extension') || ! empty($this->input('tp_extension_letter_no'));
                    $extMonths = $hasExt ? max(1, (int) ($this->input('tp_extension_months', 1) ?: 1)) : 0;
                    $rawDueDate = $this->input('tp_due_date')
                        ? rescue(fn () => Carbon::parse($this->input('tp_due_date'))->startOfDay(), null, false)
                        : $assessment?->tp_due_date?->startOfDay();

                    $defaultBase = null;
                    $endAtVal = $this->input('end_at') ?: $this->input('end_date') ?: $assessment?->end_at;
                    if ($endAtVal) {
                        $endAt = rescue(fn () => Carbon::parse($endAtVal)->startOfDay(), null, false);
                        if ($endAt) {
                            $type = $this->input('assessment_type') ?: $assessment?->assessment_type;
                            $defaultBase = app(\App\Services\AssessmentTpService::class)->calculateDefaultDueDate($type, $endAt);
                        }
                    }

                    $effectiveDue = app(\App\Services\AssessmentTpService::class)->resolveEffectiveTpDueDate($rawDueDate, $hasExt, $extMonths, $defaultBase);

                    if ($effectiveDue && $satisfiedAt->gt($effectiveDue)) {
                        $fail("Tanggal memenuhi ({$satisfiedAt->format('d/m/Y')}) tidak boleh melewati tenggat waktu tindakan perbaikan ({$effectiveDue->format('d/m/Y')}) sesuai Klausul 2.11.12 KAN U-01 Rev.3. Melewati batas waktu perbaikan berakibat pembekuan akreditasi.");
                    }
                },
            ],
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
            'tp_extension_letter_no.required_if_accepted' => 'Nomor surat resmi LPK wajib diisi saat mengajukan perpanjangan waktu tindakan perbaikan.',
            'tp_extension_letter_no.required_with' => 'Nomor surat resmi LPK wajib diisi jika tanggal surat perpanjangan diisi.',
            'tp_extension_date.required_if_accepted' => 'Tanggal surat resmi LPK wajib diisi saat mengajukan perpanjangan waktu tindakan perbaikan.',
            'tp_extension_date.required_with' => 'Tanggal surat resmi LPK wajib diisi jika nomor surat perpanjangan diisi.',
        ];
    }

    public function normalizedPayload(?Assessment $assessment = null): array
    {
        $data = $this->validated();

        unset($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);

        if (! empty($data['assessment_team'])) {
            $data['lead_assessor'] = mb_substr((string) $data['assessment_team'], 0, 100);
        } elseif (! empty($data['lead_assessor'])) {
            $data['assessment_team'] = $data['lead_assessor'];
        }

        $startAt = ! empty($data['start_at']) ? Carbon::parse($data['start_at']) : null;
        $endAt = ! empty($data['end_at']) ? Carbon::parse($data['end_at']) : null;
        $tpDueDate = ! empty($data['tp_due_date']) ? Carbon::parse($data['tp_due_date']) : $assessment?->tp_due_date;
        $tpHasExtension = (bool) ($data['tp_has_extension'] ?? false);
        if ($tpHasExtension) {
            $tpExtensionMonths = (int) ($data['tp_extension_months'] ?? 1) ?: 1;
            $tpExtensionDate = ! empty($data['tp_extension_date']) ? Carbon::parse($data['tp_extension_date']) : $assessment?->tp_extension_date;
        } else {
            $tpExtensionMonths = 0;
            $tpExtensionDate = null;
            $data['tp_extension_letter_no'] = null;
            $data['tp_extension_date'] = null;
            $data['tp_extension_notes'] = null;
        }
        $data['tp_has_extension'] = $tpHasExtension;
        $data['tp_extension_months'] = $tpExtensionMonths;
        $tpSatisfiedAt = ! empty($data['tp_satisfied_at']) ? Carbon::parse($data['tp_satisfied_at']) : $assessment?->tp_satisfied_at;

        $data['assessment_type'] = Assessment::normalizeType(
            $data['assessment_type'] ?? $assessment?->assessment_type,
            (string) ($data['title'] ?? ($assessment?->title ?: ''))
        );

        // Sinkronisasi otomatis judul asesmen saat jenis asesmen atau LPK berubah
        $targetLpk = ! empty($data['lpk_id']) ? \App\Models\Lpk::find($data['lpk_id']) : ($assessment ? $assessment->lpk : null);
        $lpkName = $targetLpk?->name;
        $newTypeLabel = $data['assessment_type'];
        $submittedTitle = trim((string) ($data['title'] ?? ''));

        if (empty($submittedTitle)) {
            $data['title'] = $lpkName ? "{$newTypeLabel} - {$lpkName}" : $newTypeLabel;
        } else {
            $knownTypePatterns = [
                'Akreditasi Awal', 'Surveilen 1 \\+ PRL', 'Surveilen 2 \\+ PRL',
                'Surveilen 1', 'Surveilen 2', 'Surveilen Tidak Terjadwal \\(STT\\)', 'Surveilen Tidak Terjadwal',
                'Perluasan Ruang Lingkup \\(PRL\\)', 'Perluasan Ruang Lingkup', 'PRL', 'STT', 'S1', 'S2', 'RA',
                'Re-Akreditasi \\(Akreditasi Ulang\\)', 'Re-Akreditasi', 'Re-asesmen', 'Akreditasi Ulang',
                'Asesmen Surveilen 1 \\(S1\\)', 'Asesmen Surveilen 2 \\(S2\\)', 'Asesmen Re-Akreditasi \\(RA\\)', 'Asesmen Akreditasi Awal'
            ];
            $pattern = '/^(' . implode('|', $knownTypePatterns) . ')\s*-\s*(.*)$/iu';
            if (preg_match($pattern, $submittedTitle, $matches)) {
                $oldPrefix = trim($matches[1]);
                $suffix = $lpkName ?: trim($matches[2]);
                $oldNormalized = Assessment::normalizeType($oldPrefix);
                if ($oldNormalized !== $newTypeLabel) {
                    $data['title'] = "{$newTypeLabel} - {$suffix}";
                } elseif ($lpkName && trim($matches[2]) !== $lpkName) {
                    $data['title'] = "{$newTypeLabel} - {$lpkName}";
                }
            } elseif (in_array(strtolower($submittedTitle), array_map('strtolower', $knownTypePatterns), true)) {
                $data['title'] = $lpkName ? "{$newTypeLabel} - {$lpkName}" : $newTypeLabel;
            }
        }

        if (! empty($data['eha_date']) && (empty($data['eha_status']) || $data['eha_status'] === Assessment::EHA_STATUS_BELUM)) {
            $parsedEha = rescue(fn () => Carbon::parse($data['eha_date'])->endOfDay(), null, false);
            if ($parsedEha && $parsedEha->lte(now())) {
                $data['eha_status'] = Assessment::EHA_STATUS_DIREKOMENDASIKAN;
            }
        }

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
            $data['assessment_type'],
            $tpExtensionDate,
            $data['eha_status'] ?? $assessment?->eha_status
        );

        return $data;
    }
}
