@extends('master')

@section('title')
Edit Client « Admin CP
@stop

@section('content')
<h1>Edit Client</h1>
<form action="/admin/clients/edit/{{ $id }}" method="post" accept-charset="utf-8">
  <input type="text" name="client_url" id="client_url" value="{{ $client_url }}"><br>
  <input type="text" name="client_name" id="client_name" value="{{ $client_name }}"><br>
  <input type="submit" name="edit" value="Edit"><br><br>
  <input type="submit" name="delete" value="Delete">
</form>
@stop
