@extends('master')

@section('title')
Places « Jonny Barnes
@stop

@section('content')
<ul>
@foreach($places as $place)
  <li><a href="/places/{{ $place->slug }}">{{ $place->name }}</a></li>
@endforeach
</ul>
@stop
