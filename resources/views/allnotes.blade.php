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
@include('templates.mapbox-links')

<script src="/assets/frontend/Autolinker.min.js"></script>
<script src="/assets/js/links.js"></script>
<script src="/assets/js/maps.js"></script>

<script src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop
