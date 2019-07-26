@extends('master')

@section('title')Likes « @stop

@section('content')
    <div class="h-feed">
        @foreach($likes as $like)
            @include('templates.like', ['like' => $like])
        @endforeach
    </div>
@stop
