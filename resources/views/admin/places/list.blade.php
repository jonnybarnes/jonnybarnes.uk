@extends('master')

@section('title')
List Places « Admin CP
@stop

@section('content')
<h1>Places</h1>
<ul>
@foreach($places as $place)
<li>{{ $place['name'] }} <a href="/admin/places/edit/{{ $place['id'] }}">edit?</a></li>
@endforeach
</ul>
<p>Createn a <a href="/admin/places/new">new entry</a>?</p>
@stop
