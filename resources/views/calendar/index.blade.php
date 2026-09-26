@extends('layouts.app')

@section('title', 'Kalender Kegiatan | SIMASADI')

@section('content')
<x-page-header
    title="Kalender kegiatan"
    subtitle="Atur jadwal asesmen lapangan dan agenda kegiatan KAN dalam tata letak Google Calendar."
/>

{{-- Google Calendar Main Shell --}}
@include('calendar.partials.calendar-shell')

@include('calendar.partials.event-popover')
@include('calendar.partials.modal-quick-add')
@endsection
