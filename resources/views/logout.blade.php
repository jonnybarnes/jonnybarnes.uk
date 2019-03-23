@extends('master')
@section('title')Logout @stop

@section('content')
    <h2>Logout</h2>
    <form action="logout" method="post">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="submit" name="submit" value="Logout">
    </form>
@stop
