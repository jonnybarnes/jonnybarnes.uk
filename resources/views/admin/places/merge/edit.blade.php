@extends('master')

@section('title')
Merge Places « Admin CP
@stop

@section('content')
<h1>Merge places</h1>
<p>When a place is deleted, it is removed from the database, and all the notes associated with it, will be re-associated with the other place.</p>
<table>
    <tr>
        <th></th>
        <th>Place 1</th>
        <th>Place 2</th>
    </tr>
    <tr>
        <th>Name</th>
        <td>{{ $place1->name }}</td>
        <td>{{ $place2->name }}</td>
    </tr>
    <tr>
        <th>Description</th>
        <td>{{ $place1->description }}</td>
        <td>{{ $place2->description }}</td>
    </tr>
    <tr>
        <th>location</th>
        <td>{{ $place1->latitude }}, {{ $place1->longitude }}</td>
        <td>{{ $place2->latitude }}, {{ $place2->longitude }}</td>
    </tr>
    <tr>
        <th>Foursquare</th>
        <td>{{ $place1->foursquare }}</td>
        <td>{{ $place2->foursquare }}</td>
    </tr>
    <tr>
        <td></td>
        <form action="/admin/places/merge" method="post">
        {{ csrf_field() }}
        <input type="hidden" name="place1" value="{{ $place1->id }}">
        <input type="hidden" name="place2" value="{{ $place2->id }}">
        <td><button type="submit" name="delete" value="1">Delete Place 1</button></td>
        <td><button type="submit" name="delete" value="2">Delete Place 2</button></td>
        </form>
    </tr>
</table>
@stop
