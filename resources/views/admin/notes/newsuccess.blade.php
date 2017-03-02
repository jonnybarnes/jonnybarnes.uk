@extends('master')

@section('title')
New Note Success « Admin CP
@stop

@section('content')
<p>Successfully created note with id: {{ $id }}. {{ $shorturl }}</p>
@stop
