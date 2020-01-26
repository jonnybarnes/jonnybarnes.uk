@extends('master')

@section('title')Like « @stop

@section('content')
    @include('templates.like', ['like' => $like])

    <!-- POSSE to Twitter -->
    <a href="https://brid.gy/publish/twitter"></a>
@stop
