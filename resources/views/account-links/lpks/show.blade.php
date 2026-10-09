@extends('layouts.app')

@section('title', $lpk->name . ' (Akun: ' . $owner->name . ') | SIMASADI')

@section('content')
    @include('lpks.partials.show-content', ['owner' => $owner])
@endsection
