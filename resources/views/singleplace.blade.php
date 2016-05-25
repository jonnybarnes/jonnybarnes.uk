@extends('master')

@section('title')
{{ $place->name }} « Places « Jonny Barnes
@stop

@section('content')
<div class="h-card">
  <h1 class="p-name">{{ $place->name }}</h1>
  <p class="p-note">{{ $place->description or 'No description'}}</p>
  <div class="map" data-latitude="{{ $place->latitude }}" data-longitude="{{ $place->longitude }}"></div>
  <p class="latlnginfo">Latitude: <span class="p-latitude">{{ $place->latitude }}</span>, longitude: <span class="p-longitude">{{ $place->longitude }}</span></p>
</div>
@stop

@section('scripts')
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js"></script>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css">

<script src="{{ elixir('assets/js/maps.js') }}"></script>
@stop
