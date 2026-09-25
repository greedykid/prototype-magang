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
            $isPrlOrStt = in_array($item->event_type, ['PRL', 'STT'], true);
            $unifiedEvents->push([
                'id' => $item->id,
                'source' => 'calendar_event',
                'category' => $isPrlOrStt ? 'ASESMEN_LAPANGAN' : 'AGENDA_INTERNAL',
                'category_label' => $isPrlOrStt ? ($item->event_type === 'PRL' ? 'Penambahan Ruang Lingkup (PRL)' : 'Surveilen Tidak Terjadwal (STT)') : 'Agenda Internal',
                'color_theme' => $isPrlOrStt ? 'emerald' : 'indigo',
                'title' => $isPrlOrStt ? $item->event_type : $item->title,
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
            $shortType = match (strtoupper(trim((string)$item->assessment_type))) {
                'S1', 'SURVEILEN 1', 'SURVEILLANCE 1' => 'S1',
                'SURVEILEN 1 + PRL', 'S1 + PRL', 'S1+PRL' => 'S1+PRL',
                'S2', 'SURVEILEN 2', 'SURVEILLANCE 2' => 'S2',
                'SURVEILEN 2 + PRL', 'S2 + PRL', 'S2+PRL' => 'S2+PRL',
                'RA', 'RE-AKREDITASI', 'REAKREDITASI', 'RE-ASESMEN', 'REASSESSMENT', 'RE-AKREDITASI (AKREDITASI ULANG)' => 'RA',
                'PRL', 'PERLUASAN RUANG LINGKUP', 'PERLUASAN LINGKUP', 'PERLUASAN RUANG LINGKUP (PRL)' => 'PRL',
                'STT', 'SURVEILEN TIDAK TERJADWAL', 'SURVEILEN TIDAK TERJADWAL (STT)' => 'STT',
                'AA', 'ASESMEN AWAL', 'INITIAL', 'AKREDITASI AWAL' => 'AA',
                default => $item->assessment_type ?: 'Asesmen',
            };

            if (in_array($shortType, ['Surveilen', 'Asesmen'], true) && preg_match('/\b(S1|S2|RA|PRL|STT|AA)\b/i', (string)$item->title, $m)) {
                $shortType = strtoupper($m[1]);
            }

            $lpkName = $item->lpk?->name ?? 'LPK Terakreditasi';
            $lpkReg = $item->lpk?->registration_number;
            $fullLpkName = $lpkReg ? ($lpkName . ' (' . $lpkReg . ')') : $lpkName;

            $unifiedEvents->push([
                'id' => $item->id,
                'source' => 'assessment',
                'category' => 'ASESMEN_LAPANGAN',
                'category_label' => 'Pelaksanaan',
                'color_theme' => 'emerald',
                'title' => $shortType,
                'lpk_id' => $item->lpk_id,
                'lpk_name' => $fullLpkName,
                'lpk_reg' => $lpkReg,
                'start_at' => $item->start_at,
                'end_at' => $item->end_at,
                'location' => $item->location ?: 'Lokasi Lapangan LPK',
                'status' => $item->status,
                'notes' => $item->notes,
                'description' => 'Program asesmen akreditasi ' . $shortType . '. Lead Asesor: ' . ($item->lead_assessor ?: 'Asesor KAN'),
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
            if ($effectiveDueDate) {
                $dueStart = CarbonImmutable::parse($effectiveDueDate)->setTime(9, 0);
                $dueEnd = $dueStart->setTime(17, 0);

                if ($dueStart->gte($queryStart->startOfDay()) && $dueStart->lte($queryEnd->endOfDay())) {
                    $statusLabel = match ($item->tp_status) {
                        Assessment::TP_STATUS_SATISFIED => 'Memenuhi Syarat (Selesai)',
                        default => $item->is_tp_overdue ? 'Melewati Batas Waktu' : (($item->days_remaining_tp !== null && $item->days_remaining_tp <= 14) ? 'Jatuh Tempo Segera' : 'Penyusunan Perbaikan'),
                    };

                    $lpkName = $item->lpk?->name ?? 'LPK Terakreditasi';
                    $lpkReg = $item->lpk?->registration_number;
                    $fullLpkName = $lpkReg ? ($lpkName . ' (' . $lpkReg . ')') : $lpkName;

                    $unifiedEvents->push([
                        'id' => 'tp_' . $item->id,
                        'source' => 'assessment_tp',
                        'category' => 'BATAS_TP',
                        'category_label' => 'Batas TP',
                        'color_theme' => 'cyan',
                        'title' => 'Batas TP',
                        'lpk_id' => $item->lpk_id,
                        'lpk_name' => $fullLpkName,
                        'lpk_reg' => $lpkReg,
                        'start_at' => $dueStart,
                        'end_at' => $dueEnd,
                        'location' => $item->location ?: 'Daring / Online',
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

            // 2c. Reminder TP dan VTP (1,5 bulan / 45 hari dari end_at) jika belum SATISFIED
            if ($item->tp_status !== Assessment::TP_STATUS_SATISFIED && $item->end_at) {
                $tpReminderDate = $item->calculateDefaultTpReminderDate();
                if ($tpReminderDate) {
                    $remStart = CarbonImmutable::parse($tpReminderDate)->setTime(9, 0);
                    $remEnd = $remStart->setTime(17, 0);

                    if ($remStart->gte($queryStart->startOfDay()) && $remStart->lte($queryEnd->endOfDay())) {
                        $lpkName = $item->lpk?->name ?? 'LPK Terakreditasi';
                        $lpkReg = $item->lpk?->registration_number;
                        $fullLpkName = $lpkReg ? ($lpkName . ' (' . $lpkReg . ')') : $lpkName;

                        $unifiedEvents->push([
                            'id' => 'reminder_tp_' . $item->id,
                            'source' => 'assessment_tp_reminder',
                            'category' => 'REMINDER',
                            'category_label' => 'Reminder',
                            'color_theme' => 'amber',
                            'title' => 'Reminder TP',
                            'lpk_id' => $item->lpk_id,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $remStart,
                            'end_at' => $remEnd,
                            'location' => $item->location ?: 'Daring / Online',
                            'status' => $item->tp_status,
                            'notes' => 'Pengingat 1,5 bulan (45 hari) dari tanggal selesai kunjungan untuk penyelesaian tindakan perbaikan (TP & VTP).',
                            'description' => 'Pengingat progres penyelesaian temuan asesmen ' . ($item->assessment_type ?: '') . ' agar tidak melebihi batas waktu 2 bulan.',
                            'lead' => $item->lead_assessor ?: 'Asesor KAN',
                            'url' => route('assessments.show', $item),
                            'edit_url' => route('assessments.show', $item),
                            'action_label' => 'Buka Detail Asesmen',
                        ]);
                    }
                }
            }

            // 2d. Reminder Penerbitan SK (10 hari setelah tp_satisfied_at jika sk_number kosong)
            if (empty($item->sk_number) && $item->tp_satisfied_at) {
                $skReminderDate = $item->calculateDefaultSkReminderDate();
                if ($skReminderDate) {
                    $skRemStart = CarbonImmutable::parse($skReminderDate)->setTime(9, 0);
                    $skRemEnd = $skRemStart->setTime(17, 0);

                    if ($skRemStart->gte($queryStart->startOfDay()) && $skRemStart->lte($queryEnd->endOfDay())) {
                        $lpkName = $item->lpk?->name ?? 'LPK Terakreditasi';
                        $lpkReg = $item->lpk?->registration_number;
                        $fullLpkName = $lpkReg ? ($lpkName . ' (' . $lpkReg . ')') : $lpkName;

                        $unifiedEvents->push([
                            'id' => 'reminder_sk_' . $item->id,
                            'source' => 'assessment_sk_reminder',
                            'category' => 'REMINDER',
                            'category_label' => 'Reminder',
                            'color_theme' => 'amber',
                            'title' => 'Reminder SK',
                            'lpk_id' => $item->lpk_id,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $skRemStart,
                            'end_at' => $skRemEnd,
                            'location' => $item->location ?: 'Sekretariat KAN',
                            'status' => 'PENDING_SK',
                            'notes' => 'Pengingat 10 hari setelah verifikasi perbaikan memenuhi syarat untuk memantau penerbitan SK Akreditasi KAN.',
                            'description' => 'Pemantauan penerbitan SK Akreditasi KAN untuk asesmen ' . ($item->assessment_type ?: '') . '.',
                            'lead' => $item->lead_assessor ?: 'Asesor KAN',
                            'url' => route('assessments.show', $item),
                            'edit_url' => route('assessments.show', $item),
                            'action_label' => 'Buka Detail Asesmen',
                        ]);
                    }
                }
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
            $lpkReg = $lpkItem->registration_number;
            $fullLpkName = $lpkReg ? ($lpkItem->name . ' (' . $lpkReg . ')') : $lpkItem->name;

            foreach ($milestones as $key => $milestone) {
                $isCompleted = ($milestone['status'] === 'COMPLETED_OR_SCHEDULED');

                // 3a. Reminder Siklus (Kuning / theme-amber) pada notice_date
                if (!empty($milestone['notice_date']) && !$isCompleted) {
                    $remDate = CarbonImmutable::parse($milestone['notice_date'])->setTime(9, 0);
                    $remDateEnd = $remDate->setTime(17, 0);

                    if ($remDate->gte($queryStart->startOfDay()) && $remDate->lte($queryEnd->endOfDay())) {
                        $unifiedEvents->push([
                            'id' => 'lpk_rem_' . $key . '_' . $lpkItem->id,
                            'source' => 'lpk_milestone_reminder',
                            'category' => 'REMINDER',
                            'category_label' => 'Reminder',
                            'color_theme' => 'amber',
                            'title' => 'Reminder ' . strtoupper($key),
                            'lpk_id' => $lpkItem->id,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $remDate,
                            'end_at' => $remDateEnd,
                            'location' => $lpkItem->address ?: 'Lokasi Lapangan LPK',
                            'status' => $milestone['status'],
                            'notes' => $milestone['description'] . ' (Periode pengingat siklus KAN)',
                            'description' => 'Target reminder siklus akreditasi KAN (' . $milestone['name'] . ') untuk ' . $lpkItem->name,
                            'lead' => null,
                            'url' => route('lpks.show', $lpkItem),
                            'edit_url' => route('assessments.create', [
                                'lpk_id' => $lpkItem->id,
                                'assessment_type' => match ($key) { 's1', 's2' => 'Surveilen', default => 'Re-asesmen' },
                                'start_at' => $remDate->format('Y-m-d\T09:00'),
                                'end_at' => $remDateEnd->format('Y-m-d\T17:00'),
                            ]),
                            'action_label' => 'Jadwalkan Asesmen',
                        ]);
                    }
                }

                // 3b. Jatuh Tempo Siklus (Merah / theme-rose) pada target_date
                if (!empty($milestone['target_date']) && !$isCompleted) {
                    $targetDate = CarbonImmutable::parse($milestone['target_date'])->setTime(9, 0);
                    $targetDateEnd = $targetDate->setTime(17, 0);

                    if ($targetDate->gte($queryStart->startOfDay()) && $targetDate->lte($queryEnd->endOfDay())) {
                        $unifiedEvents->push([
                            'id' => 'lpk_jt_' . $key . '_' . $lpkItem->id,
                            'source' => 'lpk_milestone_jt',
                            'category' => 'JATUH_TEMPO',
                            'category_label' => 'Jatuh Tempo',
                            'color_theme' => 'rose',
                            'title' => 'JT ' . strtoupper($key),
                            'lpk_id' => $lpkItem->id,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $targetDate,
                            'end_at' => $targetDateEnd,
                            'location' => $lpkItem->address ?: 'Lokasi Lapangan LPK',
                            'status' => $milestone['status'],
                            'notes' => $milestone['description'] . ' (Batas waktu jatuh tempo siklus pengawasan KAN)',
                            'description' => 'Batas jatuh tempo siklus akreditasi KAN (' . $milestone['name'] . ') untuk ' . $lpkItem->name,
                            'lead' => null,
                            'url' => route('lpks.show', $lpkItem),
                            'edit_url' => route('assessments.create', [
                                'lpk_id' => $lpkItem->id,
                                'assessment_type' => match ($key) { 's1', 's2' => 'Surveilen', default => 'Re-asesmen' },
                                'start_at' => $targetDate->format('Y-m-d\T09:00'),
                                'end_at' => $targetDateEnd->format('Y-m-d\T17:00'),
                            ]),
                            'action_label' => 'Jadwalkan Asesmen',
                        ]);
                    }
                }
            }

            // 3c. Kedaluwarsa Akreditasi pada expired_at (Merah / theme-rose)
            if ($lpkItem->expired_at) {
                $expDate = CarbonImmutable::parse($lpkItem->expired_at)->setTime(9, 0);
                $expDateEnd = $expDate->setTime(17, 0);

                if ($expDate->gte($queryStart->startOfDay()) && $expDate->lte($queryEnd->endOfDay())) {
                    $unifiedEvents->push([
                        'id' => 'lpk_exp_' . $lpkItem->id,
                        'source' => 'lpk_expired',
                        'category' => 'JATUH_TEMPO',
                        'category_label' => 'Jatuh Tempo',
                        'color_theme' => 'rose',
                        'title' => 'Kedaluwarsa',
                        'lpk_id' => $lpkItem->id,
                        'lpk_name' => $fullLpkName,
                        'lpk_reg' => $lpkReg,
                        'start_at' => $expDate,
                        'end_at' => $expDateEnd,
                        'location' => $lpkItem->address ?: 'Kantor LPK',
                        'status' => 'EXPIRED',
                        'notes' => 'Masa berlaku sertifikat akreditasi KAN berakhir pada ' . $lpkItem->expired_at->format('d/m/Y'),
                        'description' => 'Masa berlaku sertifikat akreditasi KAN untuk ' . $lpkItem->name . ' telah habis.',
                        'lead' => null,
                        'url' => route('lpks.show', $lpkItem),
                        'edit_url' => route('lpks.edit', $lpkItem),
                        'action_label' => 'Perpanjang Akreditasi',
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
            'event_type' => ['nullable', 'string', 'in:PRL,STT,AGENDA_INTERNAL'],
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
