@extends('master')

@section('title')
New Place « Admin CP
@stop

@section('content')
<h1>New Place</h1>
<form action="/admin/places/" method="post" accept-charset="utf-8">
  {{ csrf_field() }}
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
@include('templates.mapbox-links')

<script src="/assets/js/newplace.js"></script>
@stop
