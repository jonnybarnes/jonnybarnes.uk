@extends('master')

@section('title')Notes « @stop

@section('content')
            <div class="h-feed">
                <!-- the following span stops microformat parses going haywire
                generating a name property for the h-feed -->
                <span class="p-name"></span>
@foreach ($notes as $note)
                <div class="h-entry">
                    @include('templates.note', ['note' => $note])
                </div>
@endforeach
            </div>
            {!! $notes->render() !!}
@stop

@section('scripts')
        @include('templates.mapbox-links')
@stop
