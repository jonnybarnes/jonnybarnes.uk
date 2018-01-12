@extends('master')

@section('title')List Likes « Admin CP « @stop

@section('content')
            <h1>Likes</h1>
            <ul>
@foreach($likes as $like)
                <li>{{ $like['url'] }}
                    <a href="/admin/likes/{{ $like['id'] }}/edit">edit?</a>
                </li>
@endforeach
            </ul>
            <p>Create a <a href="/admin/likes/create">new like</a>?</p>
@stop
