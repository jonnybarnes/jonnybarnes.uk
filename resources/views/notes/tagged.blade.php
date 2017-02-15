@extends('master')

@section('title')
Tagged Notes « 
@stop

@section('content')
<h2>Notes tagged with <em>{{ $tag }}</em></h2>
@foreach ($notes as $note)
<div>{!! $note->note !!}
<a href="/note/{{ $note->id }}">{{ $note->human_time }}</a></div>
@endforeach
@stop
