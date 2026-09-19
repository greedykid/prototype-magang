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

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) {
            $activeDate = CarbonImmutable::createFromFormat('!Y-m-d', $dateParam);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $activeDate = CarbonImmutable::createFromFormat('!Y-m', $monthParam)->startOfMonth();
        } else {
            $activeDate = CarbonImmutable::now()->startOfDay();
        }

        $currentMonth = $activeDate->startOfMonth();
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

        $eventsByDate = $unifiedEvents->sortBy('start_at')->groupBy(fn ($ev) => $ev['start_at']->toDateString());

        // For backwards-compatibility with tests that check $events or $weeks
        $events = $calendarEvents->groupBy(fn (CalendarEvent $event): string => $event->start_at->toDateString());
        $weeks = $monthWeeks;

        $lpks = Lpk::orderBy('name')->get(['id', 'name']);
        $hoursRange = range(7, 19);

        return view('calendar.index', compact(
            'currentMonth',
            'activeDate',
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
