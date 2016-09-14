@extends('master')

@section('title')
New Place « Admin CP
@stop

@section('content')
<h1>New Place</h1>
<form action="/admin/places/new" method="post" accept-charset="utf-8">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <label for="name">Name:</label> <input type="text" name="name" id="name" placeholder="Place Name"><br>
  <label for="description">Description:</label> <input type="text" name="description" id="description" placeholder="Description"><br>
  <label for="latitude">Latitude:</label> <input type="text" name="latitude" id="latitude" placeholder="Latitude"><br>
  <label for="longitude">Longitude:</label> <input type="text" name="longitude" id="longitude" placeholder="Longitude"><br>
  <input type="submit" name="submit" value="Submit">
  <h2>Location</h2>
  <button type="button" name="locate" id="locate">Locate</button>
</form>
@stop

@section('scripts')
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css" integrity="sha384-ZDBUvY/seENyR1fE6u4p1oMFfsKVjIlkiB6TrCdXjeZVPlYanREcmZopTV8WFZ0q" crossorigin="anonymous">
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js" integrity="sha384-GgNQAMOzWcL0fePJqogHh8dCjsGKZpkBNgm3einGr0aUb9kcXvr9JeU/PDf5knja" crossorigin="anonymous"></script>

<script src="/assets/js/newplace.js"></script>
@stop
