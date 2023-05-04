@extends('master')

@section('title')New Place « Admin CP « @stop

@section('content')
    <h1>New Place</h1>
    <form action="/admin/places/" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        <div>
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" placeholder="Place Name">
        </div>
        <div>
            <label for="description">Description:</label>
            <input type="text" name="description" id="description" placeholder="Description">
        </div>
        <div>
            <label for="latitude">Latitude:</label>
            <input type="text" name="latitude" id="latitude" placeholder="Latitude">
        </div>
        <div>
            <label for="longitude">Longitude:</label>
            <input type="text" name="longitude" id="longitude" placeholder="Longitude">
        </div>
        <div>
            <input type="submit" name="submit" value="Submit">
        </div>
        <h2>Location</h2>
        <div>
            <button type="button" name="locate" id="locate">Locate</button>
        </div>
    </form>
@stop
