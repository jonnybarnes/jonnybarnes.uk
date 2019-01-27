@extends('master')

@section('title')Search « @stop

@section('content')
            <h2>Search Results</h2>
@foreach($notes as $note)
            <div class="h-entry">
@include('templates.note', ['note' => $note])
            </div>
@endforeach
{{ $notes->links() }}
@stop

@section('scripts')
            @include('templates.mapbox-links')
            <script src="/assets/js/links.js"></script>
            <script src="/assets/js/maps.js"></script>
            <link rel="stylesheet" href="/assets/highlight/zenburn.css">
@stop
