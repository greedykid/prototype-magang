@extends('layouts.app')

@section('title', 'Kalender Kegiatan | SIMASADI')

@section('content')
<x-page-header
    title="Kalender Pengawasan & Asesmen"
    subtitle="Pantau jadwal asesmen lapangan, batas waktu tindakan perbaikan (TP), dan siklus pengawasan akreditasi LPK."
/>

{{-- Google Calendar Main Shell --}}
@include('calendar.partials.calendar-shell')

@include('calendar.partials.event-popover')
@include('calendar.partials.modal-quick-add')
@endsection
