@extends('master')

@section('title')List Syndication Targets « Admin CP « @stop

@section('content')
    <h1>Syndication Targets</h1>
    @if($targets->isEmpty())
        <p>No saved syndication targets.</p>
    @else
        <ul>
        @foreach($targets as $target)
            <li>
                {{ $target['uid'] }}
                <a href="/admin/syndication/{{ $target['id'] }}/edit">edit?</a>
            </li>
        @endforeach
        </ul>
    @endif
    <p>
        Create a <a href="/admin/syndication/create">new syndication target</a>?
    </p>
@stop
