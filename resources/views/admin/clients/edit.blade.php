@extends('master')

@section('title')
Edit Client « Admin CP
@stop

@section('content')
<h1>Edit Client</h1>
<form action="/admin/clients/{{ $id }}" method="post" accept-charset="utf-8">
  {{ csrf_field() }}
  {{ method_field('PUT') }}
  <input type="text" name="client_url" id="client_url" value="{{ $client_url }}"><br>
  <input type="text" name="client_name" id="client_name" value="{{ $client_name }}"><br>
  <input type="submit" name="submit" value="Edit">
</form>
<hr>
<form action="/admin/clients/{{ $id }}" method="post">
  {{ csrf_field() }}
  {{ method_field('DELETE') }}
  <button type="submit">Delete Client</button>
</form>
@stop
