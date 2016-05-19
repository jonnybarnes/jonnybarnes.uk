@extends('master')

@section('title')
Notes « Jonny Barnes
@stop

@section('content')
    <div class="h-feed">
      <!-- the following span stops microformat parses going haywire generating
      a name property for the h-feed -->
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
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css">
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js"></script>

<script src="{{ elixir('assets/js/Autolinker.min.js') }}"></script>
<script src="{{ elixir('assets/js/links.js') }}"></script>
<script src="{{ elixir('assets/js/maps.js') }}"></script>

<script src="{{ elixir('assets/js/prism.js') }}"></script>
<link rel="stylesheet" href="{{ elixir('assets/css/prism.css') }}">
@stop
