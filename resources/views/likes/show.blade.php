@extends('master')

@section('title')Like « @stop

@section('content')
    @include('templates.like', ['like' => $like])
@stop
