@extends('master')

@section('title')Bookmark « @stop

@section('content')
    @include('templates.bookmark', ['bookmark' => $bookmark])
@stop
