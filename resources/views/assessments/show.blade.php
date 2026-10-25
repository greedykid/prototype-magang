@extends('layouts.app')

@section('title', $assessment->display_title . ' | SIMASADI')

@section('content')
    @include('assessments.partials.show-content')
@endsection
