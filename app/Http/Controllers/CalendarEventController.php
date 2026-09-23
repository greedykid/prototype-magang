<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\CalendarEvent;
use App\Models\Lpk;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarEventController extends Controller
{
    public function index(Request $request): View
    {
        $viewMode = $request->string('view')->trim()->toString() ?: 'month';
        if (!in_array($viewMode, ['month', 'week', 'day', 'agenda'], true)) {
            $viewMode = 'month';
        }

        $dateParam = $request->string('date')->trim()->toString();
        $monthParam = $request->string('month')->trim()->toString();
        $isSelected = $request->boolean('selected') || ($request->has('date') && $viewMode === 'day');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) {
            $activeDate = CarbonImmutable::createFromFormat('!Y-m-d', $dateParam);
            $currentMonth = $activeDate->startOfMonth();
            $selectedDate = $isSelected ? $activeDate : null;
        } elseif (preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $currentMonth = CarbonImmutable::createFromFormat('!Y-m', $monthParam)->startOfMonth();
            $activeDate = $currentMonth;
            $selectedDate = null;
        } else {
            $activeDate = CarbonImmutable::now()->startOfDay();
            $currentMonth = $activeDate->startOfMonth();
            $selectedDate = null;
        }

        $firstDay = $currentMonth;
        $gridStart = $firstDay->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $currentMonth->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        // Month grid days (all cells in month grid)
        $monthWeeks = [];
        for ($day = $gridStart; $day <= $gridEnd; $day = $day->addDay()) {
            $monthWeeks[] = $day;
        }

        // Mini calendar grid (always shows month of activeDate)
        $miniGridStart = $firstDay->startOfWeek(CarbonImmutable::MONDAY);
        $miniGridEnd = $currentMonth->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $miniWeeks = [];
        for ($day = $miniGridStart; $day <= $miniGridEnd; $day = $day->addDay()) {
            $miniWeeks[] = $day;
        }

        // Week view days (7 days of active week: Monday to Sunday)
        $weekStart = $activeDate->startOfWeek(CarbonImmutable::MONDAY);
        $weekEnd = $activeDate->endOfWeek(CarbonImmutable::SUNDAY);
        $weekDays = [];
        for ($day = $weekStart; $day <= $weekEnd; $day = $day->addDay()) {
            $weekDays[] = $day;
        }

        // Query date range spanning all needed dates
        $queryStart = min($gridStart, $weekStart, $activeDate)->startOfDay();
        $queryEnd = max($gridEnd, $weekEnd, $activeDate->addDays(35))->endOfDay();

        // 1. Fetch CalendarEvent
        $calendarEvents = CalendarEvent::with('lpk')
            ->whereBetween('start_at', [$queryStart, $queryEnd])
            ->orderBy('start_at')
            ->get();

        // 2. Fetch Assessment (Integrated Calendar)
        $assessments = Assessment::with('lpk')
            ->whereBetween('start_at', [$queryStart, $queryEnd])
            ->orderBy('start_at')
            ->get();

        // Standardize into unified items
        $unifiedEvents = collect();

        foreach ($calendarEvents as $item) {
            $unifiedEvents->push([
                'id' => $item->id,
                'source' => 'calendar_event',
                'category' => 'AGENDA_INTERNAL',
                'category_label' => 'Agenda Internal',
                'color_theme' => 'indigo',
                'title' => $item->title,
                'lpk_id' => $item->lpk_id,
                'lpk_name' => $item->lpk?->name ?? 'Internal SIMASADI',
                'start_at' => $item->start_at,
                'end_at' => $item->end_at,
                'location' => $item->location ?: 'Ruang Rapat / Daring',
                'status' => $item->status,
                'notes' => $item->notes,
                'description' => $item->description,
                'lead' => null,
                'url' => route('calendar.events.show', $item),
                'edit_url' => route('calendar.events.edit', $item),
            ]);
        }

        foreach ($assessments as $item) {
            $unifiedEvents->push([
                'id' => $item->id,
                'source' => 'assessment',
                'category' => 'ASESMEN_LAPANGAN',
                'category_label' => 'Asesmen Lapangan',
                'color_theme' => 'purple',
                'title' => $item->title . ($item->assessment_type ? ' (' . $item->assessment_type . ')' : ''),
                'lpk_id' => $item->lpk_id,
                'lpk_name' => $item->lpk?->name ?? 'LPK Terakreditasi',
                'start_at' => $item->start_at,
                'end_at' => $item->end_at,
                'location' => $item->location ?: 'Lokasi Lapangan LPK',
                'status' => $item->status,
                'notes' => $item->notes,
                'description' => 'Program asesmen akreditasi ' . ($item->assessment_type ?: ''),
                'lead' => $item->lead_assessor ?: 'Asesor KAN',
                'url' => route('assessments.show', $item),
                'edit_url' => route('assessments.edit', $item),
            ]);
        }

        // 2b. Sinkronisasi Batas Waktu Tindakan Perbaikan (TP & VTP) KAN ke Kalender
        $tpAssessments = Assessment::with('lpk')
            ->whereNotNull('tp_status')
            ->where('tp_status', '!=', Assessment::TP_STATUS_NONE)
            ->get();

        foreach ($tpAssessments as $item) {
            $effectiveDueDate = $item->effective_tp_due_date;
            if (! $effectiveDueDate) {
                continue;
            }

            $dueStart = CarbonImmutable::parse($effectiveDueDate)->setTime(9, 0);
            $dueEnd = $dueStart->setTime(17, 0);

            if ($dueStart->gte($queryStart->startOfDay()) && $dueStart->lte($queryEnd->endOfDay())) {
                $colorTheme = match ($item->tp_status) {
                    Assessment::TP_STATUS_SATISFIED => 'emerald',
                    default => $item->is_tp_overdue ? 'rose' : (($item->days_remaining_tp !== null && $item->days_remaining_tp <= 14) ? 'amber' : 'emerald'),
                };

                $statusLabel = match ($item->tp_status) {
                    Assessment::TP_STATUS_SATISFIED => 'Memenuhi Syarat (Selesai)',
                    default => $item->is_tp_overdue ? 'Melewati Batas Waktu' : (($item->days_remaining_tp !== null && $item->days_remaining_tp <= 14) ? 'Jatuh Tempo Segera' : 'Penyusunan Perbaikan'),
                };

                $unifiedEvents->push([
                    'id' => 'tp_' . $item->id,
                    'source' => 'assessment_tp',
                    'category' => 'TINDAKAN_PERBAIKAN',
                    'category_label' => 'Batas Waktu TP & VTP',
                    'color_theme' => $colorTheme,
                    'title' => '[Batas TP] ' . ($item->lpk?->name ?? 'LPK') . ' - ' . $item->title,
                    'lpk_id' => $item->lpk_id,
                    'lpk_name' => $item->lpk?->name ?? 'LPK Terakreditasi',
                    'start_at' => $dueStart,
                    'end_at' => $dueEnd,
                    'location' => $item->location ?: 'Daring / KANMIS',
                    'status' => $item->tp_status,
                    'notes' => 'Tenggat penyelesaian tindakan perbaikan KAN (' . $item->assessment_type_label . '). ' .
                        ($item->tp_has_extension ? 'Perpanjangan surat +1 bulan aktif (' . ($item->tp_extension_letter_no ?: 'Ada surat') . '). ' : '') .
                        'Status: ' . $statusLabel,
                    'description' => 'Target batas waktu penyelesaian tindakan perbaikan KAN: 3 bulan untuk AA, 2 bulan untuk Survailen/PRL/RA. ' . ($item->tp_notes ?: ''),
                    'lead' => $item->lead_assessor ?: 'Asesor KAN',
                    'url' => route('assessments.show', $item),
                    'edit_url' => route('assessments.show', $item),
                    'action_label' => 'Buka Detail Asesmen',
                ]);
            }
        }

        // 3. Fetch LPK Milestones (S1, S2, Re-Akreditasi / Kedaluwarsa)
        $lpksWithMilestones = Lpk::with('assessments')
            ->where(function ($q) {
                $q->whereNotNull('certificate_date')
                    ->orWhereNotNull('expired_at');
            })
            ->get();

        foreach ($lpksWithMilestones as $lpkItem) {
            $milestones = $lpkItem->surveillance_milestones;
            foreach ($milestones as $key => $milestone) {
                if (empty($milestone['target_date'])) {
                    continue;
                }

                $targetDate = CarbonImmutable::parse($milestone['target_date'])->setTime(9, 0);
                $targetDateEnd = $targetDate->setTime(17, 0);

                if ($targetDate->gte($queryStart->startOfDay()) && $targetDate->lte($queryEnd->endOfDay())) {
                    $isExpiry = ($key === 'ra');
                    $catCode = $isExpiry ? 'KEDALUWARSA' : 'SURVEILEN';
                    $catLabel = $isExpiry ? 'Kedaluwarsa Akreditasi' : 'Jatuh Tempo Surveilen';
                    $colorTheme = $isExpiry ? 'rose' : 'amber';

                    $typePrefix = match ($key) {
                        's1' => '[S1] Surveilen 1: ',
                        's2' => '[S2] Surveilen 2: ',
                        default => '[Kedaluwarsa] Akreditasi: ',
                    };

                    $statusNote = match ($milestone['status']) {
                        'COMPLETED_OR_SCHEDULED' => ' (Asesmen telah dijadwalkan atau dilaksanakan)',
                        'OVERDUE' => ' (Perhatian: Melewati target siklus pengawasan KAN)',
                        'DUE' => ' (Periode notifikasi pengawasan aktif)',
                        'EXPIRED' => ' (Sertifikat akreditasi telah kedaluwarsa)',
                        default => ' (Target siklus terjadwal)',
                    };

                    $assessmentTypeParam = match ($key) {
                        's1', 's2' => 'Surveilen',
                        default => 'Re-asesmen',
                    };

                    $createAssessmentUrl = route('assessments.create', [
                        'lpk_id' => $lpkItem->id,
                        'assessment_type' => $assessmentTypeParam,
                        'start_at' => $targetDate->format('Y-m-d\T09:00'),
                        'end_at' => $targetDateEnd->format('Y-m-d\T17:00'),
                    ]);

                    $unifiedEvents->push([
                        'id' => 'lpk_' . $key . '_' . $lpkItem->id,
                        'source' => 'lpk_milestone',
                        'category' => $catCode,
                        'category_label' => $catLabel,
                        'color_theme' => $colorTheme,
                        'title' => $typePrefix . $lpkItem->name,
                        'lpk_id' => $lpkItem->id,
                        'lpk_name' => $lpkItem->name,
                        'start_at' => $targetDate,
                        'end_at' => $targetDateEnd,
                        'location' => $lpkItem->address ?: 'Lokasi Lapangan LPK',
                        'status' => $milestone['status'],
                        'notes' => $milestone['description'] . $statusNote,
                        'description' => 'Target siklus akreditasi KAN (' . $milestone['name'] . ') untuk ' . $lpkItem->name . ' (' . $lpkItem->registration_number . ')',
                        'lead' => null,
                        'url' => route('lpks.show', $lpkItem),
                        'edit_url' => $createAssessmentUrl,
                        'action_label' => 'Jadwalkan Asesmen',
                    ]);
                }
            }
        }

        $eventsByDate = $unifiedEvents->sortBy('start_at')->groupBy(fn ($ev) => $ev['start_at']->toDateString());

        // For backwards-compatibility with tests that check $events or $weeks
        $events = $calendarEvents->groupBy(fn (CalendarEvent $event): string => $event->start_at->toDateString());
        $weeks = $monthWeeks;

        $lpks = Lpk::orderBy('name')->get(['id', 'name']);
        $hoursRange = range(7, 19);

        return view('calendar.index', compact(
            'currentMonth',
            'activeDate',
            'selectedDate',
            'viewMode',
            'monthWeeks',
            'miniWeeks',
            'weekDays',
            'weekStart',
            'weekEnd',
            'eventsByDate',
            'unifiedEvents',
            'lpks',
            'hoursRange',
            'weeks',
            'events'
        ));
    }

    public function create(Request $request): View
    {
        $event = new CalendarEvent;
        $selectedDate = $request->date('date')?->toDateString();

        return view('calendar.form', ['event' => $event, 'lpks' => Lpk::orderBy('name')->get(), 'formTitle' => 'Tambah agenda', 'selectedDate' => $selectedDate]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = CalendarEvent::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('calendar.events.show', $event)->with('success', 'Agenda berhasil ditambahkan.');
    }

    public function show(CalendarEvent $event): View
    {
        return view('calendar.show', ['event' => $event->load(['lpk', 'creator'])]);
    }

    public function edit(CalendarEvent $event): View
    {
        return view('calendar.form', ['event' => $event, 'lpks' => Lpk::orderBy('name')->get(), 'formTitle' => 'Ubah agenda']);
    }

    public function update(Request $request, CalendarEvent $event): RedirectResponse
    {
        $event->update($this->validated($request));

        return redirect()->route('calendar.events.show', $event)->with('success', 'Agenda berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        $usesSplitDateFields = $request->filled('start_date');

        if ($request->filled('start_date')) {
            $request->merge([
                'start_at' => $request->input('start_date').' '.$request->input('start_time'),
                'end_at' => $request->input('end_date').' '.$request->input('end_time'),
            ]);
        }

        return $request->validate([
            'lpk_id' => ['required', 'exists:lpks,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'start_time' => $usesSplitDateFields ? ['required', 'date_format:H:i'] : ['nullable'],
            'end_date' => $usesSplitDateFields ? ['required', 'date'] : ['nullable'],
            'end_time' => $usesSplitDateFields ? ['required', 'date_format:H:i'] : ['nullable'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:PLANNED,IN_PROGRESS,COMPLETED,CANCELLED'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
