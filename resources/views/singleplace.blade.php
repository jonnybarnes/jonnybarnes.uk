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
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css" integrity="sha384-ZDBUvY/seENyR1fE6u4p1oMFfsKVjIlkiB6TrCdXjeZVPlYanREcmZopTV8WFZ0q" crossorigin="anonymous">
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js" integrity="sha384-GgNQAMOzWcL0fePJqogHh8dCjsGKZpkBNgm3einGr0aUb9kcXvr9JeU/PDf5knja" crossorigin="anonymous"></script>

<script src="/assets/js/maps.js"></script>
@stop
