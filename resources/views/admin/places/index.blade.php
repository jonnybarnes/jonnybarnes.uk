@extends('master')

@section('title')
List Places « Admin CP
@stop

@section('content')
<h1>Places</h1>
<ul>
@foreach($places as $place)
<li>{{ $place['name'] }} <a href="/admin/places/{{ $place['id'] }}/edit">edit?</a></li>
@endforeach
</ul>
<p>Create a <a href="/admin/places/create">new entry</a>?</p>
@stop
