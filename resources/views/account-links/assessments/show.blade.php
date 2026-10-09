@extends('layouts.app')

@section('title', $assessment->display_title . ' (Akun: ' . $owner->name . ') | SIMASADI')

@section('content')
    @include('assessments.partials.show-content', ['owner' => $owner])
@endsection
