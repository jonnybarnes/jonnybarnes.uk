@extends('master')

@section('title')Merge Places « Admin CP « @stop

@section('content')
    <p>We shall be merging {{ $first->name }}. It’s location is <code>Point({{ $first->location }})</code>.</p>
    <ul>
    @foreach($places as $place)
        <li>
            <a href="/admin/places/{{ $first->id }}/merge/{{ $place->id }}">{{ $place->name }}</a>
        </li>
    @endforeach
    </ul>
@stop
