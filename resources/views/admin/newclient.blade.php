@extends('master')

@section('title')
New Client « Admin CP
@stop

@section('content')
<h1>New Client</h1>
<form action="/admin/clients/new" method="post" accept-charset="utf-8">
  <input type="hidden" name="_token" value="{{ csrftoken() }}">
  <input type="text" name="client_url" id="client_url" placeholder="client_url"><br>
  <input type="text" name="client_name" id="client_name" placeholder="client_name"><br>
  <input type="submit" name="submit" value="Submit">
</form>
@stop
