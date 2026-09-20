@extends('layouts.app')

@section('title', 'Kalender Kegiatan | SIMASADI')

@section('content')
<x-page-header
    title="Kalender kegiatan"
    subtitle="Atur jadwal asesmen lapangan dan agenda kegiatan KAN dalam tata letak Google Calendar."
/>

{{-- Google Calendar Main Shell --}}
<div class="gcal-shell">
    @include('calendar.partials.sidebar')

    <main class="gcal-main">
        @include('calendar.partials.toolbar')

        @if($viewMode === 'month')
            @include('calendar.partials.view-month')
        @elseif($viewMode === 'week')
            @include('calendar.partials.view-week')
        @elseif($viewMode === 'day')
            @include('calendar.partials.view-day')
        @else
            @include('calendar.partials.view-agenda')
        @endif
    </main>
</div>

@include('calendar.partials.event-popover')
@include('calendar.partials.modal-quick-add')
@endsection
