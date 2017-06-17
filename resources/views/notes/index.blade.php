@extends('master')

@section('title')
Notes «
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

<script defer src="/assets/js/links.js"></script>
<link rel="stylesheet" href="/assets/frontend/mapbox-gl.css">
<script defer src="/assets/js/maps.js"></script>

<script defer src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop

@if ($homepage === true)@include('templates.bio')@endif
