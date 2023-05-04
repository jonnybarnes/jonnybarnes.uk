@extends('master')

@section('title')Notes « @stop

@section('content')
            <div class="h-feed">
                <!-- the following span stops microformat parses going haywire
                generating a name property for the h-feed -->
                <span class="p-name"></span>
@foreach ($notes as $note)
                @include('templates.note', ['note' => $note])
@endforeach
            </div>
            {!! $notes->render() !!}
@stop
