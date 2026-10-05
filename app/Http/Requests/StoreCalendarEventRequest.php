<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
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
            'event_type' => [
                'nullable',
                'string',
                'max:30',
                \Illuminate\Validation\Rule::in(array_merge(
                    array_keys(\App\Models\Assessment::TYPES),
                    ['PRL', 'STT', 'AGENDA_INTERNAL']
                )),
            ],
            'description' => ['nullable', 'string'],
            'start_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:PLANNED,IN_PROGRESS,COMPLETED,CANCELLED'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
