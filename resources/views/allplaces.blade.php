@extends('master')

@section('title')
Places « 
@stop

@section('content')
<ul>
@foreach($places as $place)
  <li><a href="/places/{{ $place->slug }}">{{ $place->name }}</a></li>
@endforeach
</ul>
@stop
