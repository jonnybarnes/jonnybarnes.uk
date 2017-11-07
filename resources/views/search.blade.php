@extends('master')

@section('title')Search « @stop

@section('content')
            <h2>Search Results</h2>
@foreach($notes as $note)
            <div class="h-entry">
@include('templates.note', ['note' => $note])
            </div>
@endforeach
@stop

@section('scripts')
@include('templates.mapbox-links')

            <script src="/assets/frontend/Autolinker.min.js"></script>
            <script src="/assets/js/links.js"></script>
            <script src="/assets/js/maps.js"></script>

            <script src="/assets/prism/prism.js"></script>
            <link rel="stylesheet" href="/assets/prism/prism.css">
@stop
