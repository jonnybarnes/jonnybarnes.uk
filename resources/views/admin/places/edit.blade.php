@extends('master')

@section('title')
Edit Place « Admin CP
@stop

@section('content')
<h1>Edit Place</h1>
<form action="/admin/places/{{ $id }}" method="post" accept-charset="utf-8">
  {{ csrf_field() }}
  {{ method_field('PUT') }}
  <input type="text" name="name" id="name" value="{{ $name }}"><br>
  <input type="text" name="description" id="description" value="{{ $description }}"><br>
  <input type="text" name="latitude" id="latitude" value="{{ $latitude }}"><br>
  <input type="text" name="longitude" id="longitude" value="{{ $longitude }}"><br>
  <input type="submit" name="edit" value="Edit"><br><br>
  <input type="submit" name="delete" value="Delete">
</form>
@stop
