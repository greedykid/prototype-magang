<?php

namespace App\Http\Controllers;

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
        $month = $request->string('month')->toString();
        $currentMonth = preg_match('/^\d{4}-\d{2}$/', $month) ? CarbonImmutable::createFromFormat('!Y-m', $month) : CarbonImmutable::now()->startOfMonth();
        $firstDay = $currentMonth->startOfMonth();
        $gridStart = $firstDay->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $currentMonth->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $events = CalendarEvent::with('lpk')->whereBetween('start_at', [$gridStart->startOfDay(), $gridEnd->endOfDay()])->orderBy('start_at')->get()->groupBy(fn (CalendarEvent $event): string => $event->start_at->toDateString());
        $weeks = [];
        for ($day = $gridStart; $day <= $gridEnd; $day = $day->addDay()) {
            $weeks[] = $day;
        }

        return view('calendar.index', compact('currentMonth', 'weeks', 'events'));
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
