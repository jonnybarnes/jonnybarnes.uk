@extends('master')

@section('title')Merge Places « Admin CP « @stop

@section('content')
    <p>We shall be merging {{ $first->name }}. It’s location is <code>{{ $first->latitude }}, {{ $first->longitude }}</code>.</p>
    <ul>
    @foreach($places as $place)
        <li>
            <a href="/admin/places/{{ $first->id }}/merge/{{ $place->id }}">{{ $place->name }}</a>
        </li>
    @endforeach
    </ul>
@stop
