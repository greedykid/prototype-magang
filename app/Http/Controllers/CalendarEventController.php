<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEventRequest;
use App\Models\Assessment;
use App\Models\CalendarEvent;
use App\Models\Lpk;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CalendarEventController extends Controller
{
    public function index(Request $request): View
    {
        $viewMode = $request->string('view')->trim()->toString() ?: 'month';
        if (! in_array($viewMode, ['month', 'week', 'day', 'agenda'], true)) {
            $viewMode = 'month';
        }

        $dateParam = $request->string('date')->trim()->toString();
        $monthParam = $request->string('month')->trim()->toString();
        $highlightParam = $request->string('highlight')->trim()->toString();

        // Resolusi tanggal aktif secara otomatis dari parameter highlight bila date tidak disertakan
        if (empty($dateParam) && ! empty($highlightParam)) {
            $dateParam = $this->resolveDateFromHighlight($highlightParam) ?: $dateParam;
        }

        $isSelected = $request->boolean('selected') || ! empty($highlightParam) || ($request->has('date') && $viewMode === 'day');

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

        // Seluruh hari pada kisi kalender bulanan
        $monthWeeks = [];
        for ($day = $gridStart; $day <= $gridEnd; $day = $day->addDay()) {
            $monthWeeks[] = $day;
        }

        // Mini kalender selalu merujuk pada bulan aktif
        $miniWeeks = $monthWeeks;

        // 7 hari pada tampilan mingguan aktif (Senin s.d. Minggu)
        $weekStart = $activeDate->startOfWeek(CarbonImmutable::MONDAY);
        $weekEnd = $activeDate->endOfWeek(CarbonImmutable::SUNDAY);
        $weekDays = [];
        for ($day = $weekStart; $day <= $weekEnd; $day = $day->addDay()) {
            $weekDays[] = $day;
        }

        // Rentang tanggal query mencakup seluruh tanggal yang ditampilkan
        $queryStart = min($gridStart, $weekStart, $activeDate)->startOfDay();
        $queryEnd = max($gridEnd, $weekEnd, $activeDate->addDays(35))->endOfDay();

        $user = $request->user();
        $picFilter = $request->input('pic_id') ?: $request->input('pic');

        // Memuat seluruh sumber entitas kalender secara modular
        $calendarEventResults = $this->fetchCalendarEvents($queryStart, $queryEnd, $user, $picFilter);
        $assessmentResults = $this->fetchAssessmentEvents($queryStart, $queryEnd, $user, $picFilter);
        $tpResults = $this->fetchAssessmentTpEvents($queryStart, $queryEnd, $user, $picFilter);
        $milestoneResults = $this->fetchLpkMilestoneEvents($queryStart, $queryEnd, $user, $picFilter);

        $unifiedEvents = collect()
            ->concat($calendarEventResults['events'])
            ->concat($assessmentResults)
            ->concat($tpResults)
            ->concat($milestoneResults);

        $eventsByDate = $this->groupEventsByDate($unifiedEvents);

        // Kompatibilitas pengujian otomatis yang mengakses $events dan $weeks
        $events = $calendarEventResults['raw_models']->groupBy(fn (CalendarEvent $event): string => $event->start_at->toDateString());
        $weeks = $monthWeeks;

        $pics = $user ? $user->getAccessiblePics() : collect();
        $lpks = Lpk::accessibleBy($user)
            ->when($picFilter, fn ($q) => $q->where('pic_id', $picFilter))
            ->orderBy('name')
            ->get(['id', 'name', 'accreditation_number', 'registration_number', 'no_reg']);
        $hoursRange = range(7, 19);

        $viewData = compact(
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
            'pics',
            'picFilter',
            'hoursRange',
            'weeks',
            'events',
            'highlightParam'
        );
        $viewData['highlightId'] = $highlightParam;

        if ($request->ajax() && $request->hasHeader('X-Partial-Content')) {
            return view('calendar.partials.calendar-shell', $viewData);
        }

        return view('calendar.index', $viewData);
    }

    public function create(Request $request): View
    {
        $event = new CalendarEvent;
        $selectedDate = $request->date('date')?->toDateString();

        return view('calendar.form', ['event' => $event, 'lpks' => Lpk::manageableBy($request->user())->orderBy('name')->get(), 'formTitle' => 'Tambah agenda', 'selectedDate' => $selectedDate]);
    }

    public function store(CalendarEventRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($request->filled('lpk_id')) {
            $lpk = Lpk::find($request->input('lpk_id'));
            if ($lpk) {
                if ($user && ! $lpk->canManage($user)) {
                    abort(403, 'Anda tidak memiliki hak untuk menambah agenda pada LPK ini.');
                }
                if ($lpk->status === 'INACTIVE') {
                    return back()->withInput()->with('error', 'Tidak dapat menambahkan agenda pada LPK yang berstatus nonaktif.');
                }
            }
        }

        $event = CalendarEvent::create($request->validated() + ['created_by' => $user->id]);

        return redirect()->route('calendar.events.show', $event)->with('success', 'Agenda berhasil ditambahkan.');
    }

    public function show(CalendarEvent $event, Request $request): View
    {
        $user = $request->user();
        if ($user && $event->lpk && ! $event->lpk->canView($user)) {
            abort(403, 'Anda tidak memiliki hak untuk melihat agenda ini.');
        }

        return view('calendar.show', ['event' => $event->load(['lpk', 'creator'])]);
    }

    public function edit(CalendarEvent $event, Request $request): View
    {
        $user = $request->user();
        if (! $this->canUserManageEvent($user, $event)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah agenda ini.');
        }

        return view('calendar.form', ['event' => $event, 'lpks' => Lpk::manageableBy($user)->orderBy('name')->get(), 'formTitle' => 'Ubah agenda']);
    }

    public function update(CalendarEventRequest $request, CalendarEvent $event): RedirectResponse
    {
        $user = $request->user();
        if (! $this->canUserManageEvent($user, $event)) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah agenda ini.');
        }

        $validated = $request->validated();
        if (! empty($validated['lpk_id']) && (int) $validated['lpk_id'] !== (int) $event->lpk_id) {
            $newLpk = Lpk::find($validated['lpk_id']);
            if ($newLpk) {
                if ($user && ! $newLpk->canManage($user)) {
                    abort(403, 'Anda tidak memiliki hak untuk memindahkan agenda ke LPK ini.');
                }
                if ($newLpk->status === 'INACTIVE') {
                    return back()->withInput()->with('error', 'Tidak dapat memindahkan agenda ke LPK yang berstatus nonaktif.');
                }
            }
        }

        $event->update($validated);

        return redirect()->route('calendar.events.show', $event)->with('success', 'Agenda berhasil diperbarui.');
    }

    public function destroy(CalendarEvent $event, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $this->canUserManageEvent($user, $event)) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus agenda ini.');
        }

        $title = $event->title;
        $event->delete();

        Log::info('Calendar event deleted', [
            'event_id' => $event->id,
            'title' => $title,
            'deleted_by' => $user?->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('calendar.index')->with('success', "Agenda \"{$title}\" berhasil dihapus.");
    }

    /**
     * Resolusi tanggal aktif dari target parameter highlight bila date tidak disertakan.
     */
    protected function resolveDateFromHighlight(string $highlightParam): ?string
    {
        if (str_starts_with($highlightParam, 'tp_')) {
            $tpId = (int) substr($highlightParam, 3);
            $tpAss = Assessment::find($tpId);
            return ($tpAss?->effective_tp_due_date ?: $tpAss?->start_at)?->toDateString();
        }

        if (str_starts_with($highlightParam, 'reminder_tp_')) {
            $tpId = (int) substr($highlightParam, 12);
            $tpAss = Assessment::find($tpId);
            if ($tpAss && $tpAss->end_at) {
                $remDate = $tpAss->calculateDefaultTpReminderDate();
                return $remDate ? CarbonImmutable::parse($remDate)->toDateString() : null;
            }
            return null;
        }

        if (str_starts_with($highlightParam, 'reminder_sk_')) {
            $skId = (int) substr($highlightParam, 12);
            $skAss = Assessment::find($skId);
            if ($skAss) {
                $skDate = $skAss->calculateDefaultSkReminderDate();
                return $skDate ? CarbonImmutable::parse($skDate)->toDateString() : null;
            }
            return null;
        }

        if (str_starts_with($highlightParam, 'lpk_jt_') || str_starts_with($highlightParam, 'lpk_rem_')) {
            $parts = explode('_', $highlightParam);
            if (count($parts) >= 4) {
                $type = $parts[1];
                $code = $parts[2];
                $lpkId = (int) $parts[3];
                $lpkItem = Lpk::find($lpkId);
                if ($lpkItem) {
                    $ms = $lpkItem->surveillance_milestones[$code] ?? null;
                    if ($ms) {
                        $target = ($type === 'rem' ? ($ms['notice_date'] ?? $ms['target_date']) : ($ms['target_date'] ?? $ms['notice_date']));
                        return $target ? CarbonImmutable::parse($target)->toDateString() : null;
                    }
                }
            }
            return null;
        }

        if (str_starts_with($highlightParam, 'lpk_exp_')) {
            $lpkId = (int) substr($highlightParam, 8);
            $lpkItem = Lpk::find($lpkId);
            return $lpkItem?->expired_at ? CarbonImmutable::parse($lpkItem->expired_at)->toDateString() : null;
        }

        if (str_starts_with($highlightParam, 'event-')) {
            $cevId = (int) substr($highlightParam, 6);
            $cev = CalendarEvent::find($cevId);
            return $cev?->start_at?->toDateString();
        }

        $cleanHighlight = str_replace('assessment-', '', $highlightParam);
        if (is_numeric($cleanHighlight) && (int) $cleanHighlight > 0) {
            $assId = (int) $cleanHighlight;
            $ass = Assessment::find($assId);
            return $ass?->start_at?->toDateString();
        }

        return null;
    }

    /**
     * Memuat agenda internal kalender dan memformat ke bentuk entitas seragam.
     *
     * @return array{events: Collection<int, array<string, mixed>>, raw_models: Collection<int, CalendarEvent>}
     */
    protected function fetchCalendarEvents(CarbonImmutable $queryStart, CarbonImmutable $queryEnd, ?\App\Models\User $user, ?string $picFilter): array
    {
        $isPic = $user && $user->isPic();
        $isAdmin = $user && $user->isAdmin();

        $calendarEvents = CalendarEvent::with('lpk')
            ->when($isPic, fn ($q) => $q->where(function ($sub) use ($user) {
                $sub->whereNull('lpk_id')
                    ->orWhereHas('lpk', fn ($lq) => $lq->accessibleBy($user));
            }))
            ->when($picFilter, fn ($q) => $q->where(function ($sub) use ($picFilter) {
                $sub->whereHas('lpk', fn ($lq) => $lq->where('pic_id', $picFilter))
                    ->orWhere(fn ($sq) => $sq->whereNull('lpk_id')->where('created_by', $picFilter));
            }))
            ->where('start_at', '<=', $queryEnd)
            ->where('end_at', '>=', $queryStart)
            ->orderBy('start_at')
            ->get();

        $events = $calendarEvents->map(function ($item) use ($isAdmin, $user) {
            $typeStr = strtoupper(trim((string) $item->event_type));
            $isInternal = ($typeStr === 'AGENDA_INTERNAL' || empty($typeStr));

            $shortType = match (true) {
                str_contains($typeStr, 'S1 + PRL') || str_contains($typeStr, 'SURVEILEN 1 + PRL') => 'S1+PRL',
                str_contains($typeStr, 'S2 + PRL') || str_contains($typeStr, 'SURVEILEN 2 + PRL') => 'S2+PRL',
                str_contains($typeStr, 'SURVEILEN 1') || $typeStr === 'S1' => 'S1',
                str_contains($typeStr, 'SURVEILEN 2') || $typeStr === 'S2' => 'S2',
                str_contains($typeStr, 'RE-AKREDITASI') || str_contains($typeStr, 'REAKREDITASI') || $typeStr === 'RA' => 'RA',
                str_contains($typeStr, 'PERLUASAN') || str_contains($typeStr, 'PRL') => 'PRL',
                str_contains($typeStr, 'STT') || str_contains($typeStr, 'TIDAK TERJADWAL') => 'STT',
                str_contains($typeStr, 'AKREDITASI AWAL') || $typeStr === 'AA' => 'AA',
                default => $item->event_type ?: 'Agenda',
            };

            $canManage = $isAdmin || ($item->lpk_id ? ($item->lpk && $item->lpk->canManage($user)) : ($item->created_by === $user?->id));

            return [
                'id' => $item->id,
                'source' => 'calendar_event',
                'category' => $isInternal ? 'AGENDA_INTERNAL' : 'ASESMEN_LAPANGAN',
                'category_label' => $isInternal ? 'Agenda Internal' : ($item->event_type ?: 'Asesmen'),
                'color_theme' => $isInternal ? 'indigo' : 'emerald',
                'title' => $isInternal ? $item->title : $shortType,
                'lpk_id' => $item->lpk_id,
                'pic_id' => $item->lpk?->pic_id ?? $item->created_by,
                'lpk_name' => $item->lpk?->name ?? 'Internal SIMASADI',
                'start_at' => $item->start_at,
                'end_at' => $item->end_at,
                'location' => $item->location ?: 'Ruang Rapat / Daring',
                'status' => $item->status,
                'notes' => $item->notes,
                'description' => $item->description,
                'lead' => null,
                'can_manage' => $canManage,
                'url' => route('calendar.events.show', $item),
                'edit_url' => $canManage ? route('calendar.events.edit', $item) : null,
            ];
        });

        return [
            'events' => $events,
            'raw_models' => $calendarEvents,
        ];
    }

    /**
     * Memuat pelaksanaan asesmen lapangan ke entitas kalender seragam.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function fetchAssessmentEvents(CarbonImmutable $queryStart, CarbonImmutable $queryEnd, ?\App\Models\User $user, ?string $picFilter): Collection
    {
        $isPic = $user && $user->isPic();
        $isAdmin = $user && $user->isAdmin();

        $assessments = Assessment::with('lpk')
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->when($picFilter, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->where('pic_id', $picFilter)))
            ->where('start_at', '<=', $queryEnd)
            ->where('end_at', '>=', $queryStart)
            ->orderBy('start_at')
            ->get();

        return $assessments->map(function ($item) use ($isAdmin, $user) {
            $shortType = match (strtoupper(trim((string) $item->assessment_type))) {
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

            if (in_array($shortType, ['Surveilen', 'Asesmen'], true) && preg_match('/\b(S1|S2|RA|PRL|STT|AA)\b/i', (string) $item->title, $m)) {
                $shortType = strtoupper($m[1]);
            }

            $lpkName = $item->lpk?->name ?? 'LPK Terakreditasi';
            $lpkReg = $item->lpk?->registration_number;
            $fullLpkName = $lpkReg ? ($lpkName . ' (' . $lpkReg . ')') : $lpkName;

            $canManage = $isAdmin || ($item->lpk && $item->lpk->canManage($user));

            return [
                'id' => $item->id,
                'source' => 'assessment',
                'category' => 'ASESMEN_LAPANGAN',
                'category_label' => 'Pelaksanaan',
                'color_theme' => 'emerald',
                'title' => $shortType,
                'lpk_id' => $item->lpk_id,
                'pic_id' => $item->lpk?->pic_id ?? $item->created_by,
                'lpk_name' => $fullLpkName,
                'lpk_reg' => $lpkReg,
                'start_at' => $item->start_at,
                'end_at' => $item->end_at,
                'location' => $item->location ?: 'Lokasi Lapangan LPK',
                'status' => $item->status,
                'notes' => $item->notes,
                'description' => 'Program asesmen akreditasi ' . $shortType . '. Lead Asesor: ' . ($item->lead_assessor ?: 'Asesor KAN'),
                'lead' => $item->lead_assessor ?: 'Asesor KAN',
                'can_manage' => $canManage,
                'url' => route('assessments.show', $item),
                'edit_url' => $canManage ? route('assessments.edit', $item) : null,
            ];
        });
    }

    /**
     * Memuat batas waktu tindakan perbaikan (TP) dan pengingat (Reminder) ke kalender.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function fetchAssessmentTpEvents(CarbonImmutable $queryStart, CarbonImmutable $queryEnd, ?\App\Models\User $user, ?string $picFilter): Collection
    {
        $isPic = $user && $user->isPic();
        $isAdmin = $user && $user->isAdmin();

        $tpAssessments = Assessment::with('lpk')
            ->whereNotNull('tp_status')
            ->where('tp_status', '!=', Assessment::TP_STATUS_NONE)
            ->where(function ($q) use ($queryStart, $queryEnd) {
                $q->whereBetween('tp_due_date', [$queryStart->toDateString(), $queryEnd->toDateString()])
                    ->orWhereBetween('tp_satisfied_at', [$queryStart->subDays(15)->toDateString(), $queryEnd->toDateString()])
                    ->orWhereBetween('end_at', [$queryStart->subMonths(5), $queryEnd->endOfDay()]);
            })
            ->when($isPic, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->accessibleBy($user)))
            ->when($picFilter, fn ($q) => $q->whereHas('lpk', fn ($lq) => $lq->where('pic_id', $picFilter)))
            ->get();

        $events = collect();

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

                    $canManage = $isAdmin || ($item->lpk && $item->lpk->canManage($user));

                    $events->push([
                        'id' => 'tp_' . $item->id,
                        'source' => 'assessment_tp',
                        'category' => 'BATAS_TP',
                        'category_label' => 'Batas TP',
                        'color_theme' => 'cyan',
                        'title' => 'Batas TP',
                        'lpk_id' => $item->lpk_id,
                        'pic_id' => $item->lpk?->pic_id ?? $item->created_by,
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
                        'can_manage' => $canManage,
                        'url' => route('assessments.show', $item),
                        'edit_url' => $canManage ? route('assessments.show', $item) : null,
                        'action_label' => 'Buka Detail Asesmen',
                    ]);
                }
            }

            // Reminder TP dan VTP (1,5 bulan / 45 hari dari end_at) jika belum SATISFIED
            if ($item->tp_status !== Assessment::TP_STATUS_SATISFIED && $item->end_at) {
                $tpReminderDate = $item->calculateDefaultTpReminderDate();
                if ($tpReminderDate) {
                    $remStart = CarbonImmutable::parse($tpReminderDate)->setTime(9, 0);
                    $remEnd = $remStart->setTime(17, 0);

                    if ($remStart->gte($queryStart->startOfDay()) && $remStart->lte($queryEnd->endOfDay())) {
                        $lpkName = $item->lpk?->name ?? 'LPK Terakreditasi';
                        $lpkReg = $item->lpk?->registration_number;
                        $fullLpkName = $lpkReg ? ($lpkName . ' (' . $lpkReg . ')') : $lpkName;

                        $canManage = $isAdmin || ($item->lpk && $item->lpk->canManage($user));

                        $events->push([
                            'id' => 'reminder_tp_' . $item->id,
                            'source' => 'assessment_tp_reminder',
                            'category' => 'REMINDER',
                            'category_label' => 'Reminder',
                            'color_theme' => 'amber',
                            'title' => 'Reminder TP',
                            'lpk_id' => $item->lpk_id,
                            'pic_id' => $item->lpk?->pic_id ?? $item->created_by,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $remStart,
                            'end_at' => $remEnd,
                            'location' => $item->location ?: 'Daring / Online',
                            'status' => $item->tp_status,
                            'notes' => 'Pengingat 1,5 bulan (45 hari) dari tanggal selesai kunjungan untuk penyelesaian tindakan perbaikan (TP & VTP).',
                            'description' => 'Pengingat progres penyelesaian temuan asesmen ' . ($item->assessment_type ?: '') . ' agar tidak melebihi batas waktu 2 bulan.',
                            'lead' => $item->lead_assessor ?: 'Asesor KAN',
                            'can_manage' => $canManage,
                            'url' => route('assessments.show', $item),
                            'edit_url' => $canManage ? route('assessments.show', $item) : null,
                            'action_label' => 'Buka Detail Asesmen',
                        ]);
                    }
                }
            }

            // Reminder Penerbitan SK (10 hari setelah tp_satisfied_at jika sk_number kosong)
            if (empty($item->sk_number) && $item->tp_satisfied_at) {
                $skReminderDate = $item->calculateDefaultSkReminderDate();
                if ($skReminderDate) {
                    $skRemStart = CarbonImmutable::parse($skReminderDate)->setTime(9, 0);
                    $skRemEnd = $skRemStart->setTime(17, 0);

                    if ($skRemStart->gte($queryStart->startOfDay()) && $skRemStart->lte($queryEnd->endOfDay())) {
                        $lpkName = $item->lpk?->name ?? 'LPK Terakreditasi';
                        $lpkReg = $item->lpk?->registration_number;
                        $fullLpkName = $lpkReg ? ($lpkName . ' (' . $lpkReg . ')') : $lpkName;

                        $canManage = $isAdmin || ($item->lpk && $item->lpk->canManage($user));

                        $events->push([
                            'id' => 'reminder_sk_' . $item->id,
                            'source' => 'assessment_sk_reminder',
                            'category' => 'REMINDER',
                            'category_label' => 'Reminder',
                            'color_theme' => 'amber',
                            'title' => 'Reminder SK',
                            'lpk_id' => $item->lpk_id,
                            'pic_id' => $item->lpk?->pic_id ?? $item->created_by,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $skRemStart,
                            'end_at' => $skRemEnd,
                            'location' => $item->location ?: 'Sekretariat KAN',
                            'status' => 'PENDING_SK',
                            'notes' => 'Pengingat 10 hari setelah verifikasi perbaikan memenuhi syarat untuk memantau penerbitan SK Akreditasi KAN.',
                            'description' => 'Pemantauan penerbitan SK Akreditasi KAN untuk asesmen ' . ($item->assessment_type ?: '') . '.',
                            'lead' => $item->lead_assessor ?: 'Asesor KAN',
                            'can_manage' => $canManage,
                            'url' => route('assessments.show', $item),
                            'edit_url' => $canManage ? route('assessments.show', $item) : null,
                            'action_label' => 'Buka Detail Asesmen',
                        ]);
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Memuat milestone siklus pengawasan LPK (S1, S2, Re-akreditasi) dan tanggal kedaluwarsa ke kalender.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function fetchLpkMilestoneEvents(CarbonImmutable $queryStart, CarbonImmutable $queryEnd, ?\App\Models\User $user, ?string $picFilter): Collection
    {
        $isAdmin = $user && $user->isAdmin();
        $cycleMinDate = $queryStart->subMonths(60)->toDateString();
        $cycleMaxDate = $queryEnd->addMonths(2)->toDateString();
        $expiredMinDate = $queryStart->subMonths(6)->toDateString();
        $expiredMaxDate = $queryEnd->addMonths(12)->toDateString();

        $lpksWithMilestones = Lpk::accessibleBy($user)
            ->select(['id', 'name', 'registration_number', 'pic_id', 'status', 'certificate_date', 'expired_at', 'address'])
            ->with(['assessments' => fn ($q) => $q->select(['id', 'lpk_id', 'title', 'assessment_type', 'start_at', 'end_at', 'status', 'tp_status', 'tp_satisfied_at', 'sk_number'])])
            ->when($picFilter, fn ($q) => $q->where('pic_id', $picFilter))
            ->where(function ($q) use ($cycleMinDate, $cycleMaxDate, $expiredMinDate, $expiredMaxDate) {
                $q->whereBetween('certificate_date', [$cycleMinDate, $cycleMaxDate])
                    ->orWhereBetween('expired_at', [$expiredMinDate, $expiredMaxDate]);
            })
            ->get();

        $events = collect();

        foreach ($lpksWithMilestones as $lpkItem) {
            $canManageLpk = $isAdmin || $lpkItem->canManage($user);
            $milestones = $lpkItem->surveillance_milestones;
            $lpkReg = $lpkItem->registration_number;
            $fullLpkName = $lpkReg ? ($lpkItem->name . ' (' . $lpkReg . ')') : $lpkItem->name;

            foreach ($milestones as $key => $milestone) {
                $isCompleted = in_array($milestone['status'], ['COMPLETED_OR_SCHEDULED', 'SUSPENDED'], true);

                // Reminder Siklus (Kuning / theme-amber) pada notice_date
                if (! empty($milestone['notice_date']) && ! $isCompleted) {
                    $remDate = CarbonImmutable::parse($milestone['notice_date'])->setTime(9, 0);
                    $remDateEnd = $remDate->setTime(17, 0);

                    if ($remDate->gte($queryStart->startOfDay()) && $remDate->lte($queryEnd->endOfDay())) {
                        $events->push([
                            'id' => 'lpk_rem_' . $key . '_' . $lpkItem->id,
                            'source' => 'lpk_milestone_reminder',
                            'category' => 'REMINDER',
                            'category_label' => 'Reminder',
                            'color_theme' => 'amber',
                            'title' => 'Reminder ' . strtoupper($key),
                            'lpk_id' => $lpkItem->id,
                            'pic_id' => $lpkItem->pic_id,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $remDate,
                            'end_at' => $remDateEnd,
                            'location' => $lpkItem->address ?: 'Lokasi Lapangan LPK',
                            'status' => $milestone['status'],
                            'notes' => $milestone['description'] . ' (Periode pengingat siklus KAN)',
                            'description' => 'Target reminder siklus akreditasi KAN (' . $milestone['name'] . ') untuk ' . $lpkItem->name,
                            'lead' => null,
                            'can_manage' => $canManageLpk,
                            'url' => route('lpks.show', $lpkItem),
                            'edit_url' => $canManageLpk ? route('assessments.create', [
                                'lpk_id' => $lpkItem->id,
                                'assessment_type' => match ($key) { 's1', 's2' => 'Surveilen', default => 'Re-asesmen' },
                                'start_at' => $remDate->format('Y-m-d\T09:00'),
                                'end_at' => $remDateEnd->format('Y-m-d\T17:00'),
                            ]) : null,
                            'action_label' => 'Jadwalkan Asesmen',
                        ]);
                    }
                }

                // Jatuh Tempo Siklus (Merah / theme-rose) pada target_date
                if (! empty($milestone['target_date']) && ! $isCompleted) {
                    $targetDate = CarbonImmutable::parse($milestone['target_date'])->setTime(9, 0);
                    $targetDateEnd = $targetDate->setTime(17, 0);

                    if ($targetDate->gte($queryStart->startOfDay()) && $targetDate->lte($queryEnd->endOfDay())) {
                        $events->push([
                            'id' => 'lpk_jt_' . $key . '_' . $lpkItem->id,
                            'source' => 'lpk_milestone_jt',
                            'category' => 'JATUH_TEMPO',
                            'category_label' => 'Jatuh Tempo',
                            'color_theme' => 'rose',
                            'title' => 'JT ' . strtoupper($key),
                            'lpk_id' => $lpkItem->id,
                            'pic_id' => $lpkItem->pic_id,
                            'lpk_name' => $fullLpkName,
                            'lpk_reg' => $lpkReg,
                            'start_at' => $targetDate,
                            'end_at' => $targetDateEnd,
                            'location' => $lpkItem->address ?: 'Lokasi Lapangan LPK',
                            'status' => $milestone['status'],
                            'notes' => $milestone['description'] . ' (Batas waktu jatuh tempo siklus pengawasan KAN)',
                            'description' => 'Batas jatuh tempo siklus akreditasi KAN (' . $milestone['name'] . ') untuk ' . $lpkItem->name,
                            'lead' => null,
                            'can_manage' => $canManageLpk,
                            'url' => route('lpks.show', $lpkItem),
                            'edit_url' => $canManageLpk ? route('assessments.create', [
                                'lpk_id' => $lpkItem->id,
                                'assessment_type' => match ($key) { 's1', 's2' => 'Surveilen', default => 'Re-asesmen' },
                                'start_at' => $targetDate->format('Y-m-d\T09:00'),
                                'end_at' => $targetDateEnd->format('Y-m-d\T17:00'),
                            ]) : null,
                            'action_label' => 'Jadwalkan Asesmen',
                        ]);
                    }
                }
            }

            // Kedaluwarsa Akreditasi pada expired_at (Merah / theme-rose)
            if ($lpkItem->expired_at) {
                $expDate = CarbonImmutable::parse($lpkItem->expired_at)->setTime(9, 0);
                $expDateEnd = $expDate->setTime(17, 0);

                if ($expDate->gte($queryStart->startOfDay()) && $expDate->lte($queryEnd->endOfDay())) {
                    $events->push([
                        'id' => 'lpk_exp_' . $lpkItem->id,
                        'source' => 'lpk_expired',
                        'category' => 'JATUH_TEMPO',
                        'category_label' => 'Kedaluwarsa',
                        'color_theme' => 'rose',
                        'title' => 'Kedaluwarsa',
                        'lpk_id' => $lpkItem->id,
                        'pic_id' => $lpkItem->pic_id,
                        'lpk_name' => $fullLpkName,
                        'lpk_reg' => $lpkReg,
                        'start_at' => $expDate,
                        'end_at' => $expDateEnd,
                        'location' => $lpkItem->address ?: 'Kantor LPK',
                        'status' => 'EXPIRED',
                        'notes' => 'Masa berlaku sertifikat akreditasi KAN berakhir pada ' . $lpkItem->expired_at->format('d/m/Y'),
                        'description' => 'Masa berlaku sertifikat akreditasi KAN untuk ' . $lpkItem->name . ' telah habis.',
                        'lead' => null,
                        'can_manage' => $canManageLpk,
                        'url' => route('lpks.show', $lpkItem),
                        'edit_url' => $canManageLpk ? route('lpks.edit', $lpkItem) : null,
                        'action_label' => 'Perpanjang Akreditasi',
                    ]);
                }
            }
        }

        return $events;
    }

    /**
     * Mengelompokkan entitas kegiatan ke tanggal-tanggal terkait, termasuk rentang multi-hari.
     *
     * @param  Collection<int, array<string, mixed>>  $unifiedEvents
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    protected function groupEventsByDate(Collection $unifiedEvents): Collection
    {
        $eventsByDate = collect();
        foreach ($unifiedEvents->sortBy('start_at') as $ev) {
            $startDate = $ev['start_at']->toDateString();
            $endDate = ($ev['end_at'] ?? $ev['start_at'])->toDateString();

            if ($startDate === $endDate) {
                if (! $eventsByDate->has($startDate)) {
                    $eventsByDate->put($startDate, collect());
                }
                $eventsByDate->get($startDate)->push($ev);
            } else {
                $startCarbon = CarbonImmutable::parse($startDate);
                $endCarbon = CarbonImmutable::parse($endDate);
                $totalDays = (int) $startCarbon->diffInDays($endCarbon) + 1;
                $curr = $startCarbon;

                while ($curr->lte($endCarbon)) {
                    $currKey = $curr->toDateString();
                    $dayIndex = (int) $startCarbon->diffInDays($curr) + 1;

                    $evCopy = $ev;
                    $evCopy['is_multi_day'] = true;
                    $evCopy['is_range_start'] = ($currKey === $startDate);
                    $evCopy['is_range_end'] = ($currKey === $endDate);
                    $evCopy['range_day_index'] = $dayIndex;
                    $evCopy['range_total_days'] = $totalDays;

                    if (! $eventsByDate->has($currKey)) {
                        $eventsByDate->put($currKey, collect());
                    }
                    $eventsByDate->get($currKey)->push($evCopy);

                    $curr = $curr->addDay();
                }
            }
        }

        return $eventsByDate;
    }

    protected function canUserManageEvent(?\App\Models\User $user, CalendarEvent $event): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($event->lpk_id && $event->lpk) {
            return $event->lpk->canManage($user);
        }

        return (int) $event->created_by === (int) $user->id;
    }
}
