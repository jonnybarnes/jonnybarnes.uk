@extends('master')

@section('title')
New Contact « Admin CP
@stop

@section('content')
<h1>New Contact</h1>
<form action="/admin/contacts/new" method="post" accept-charset="utf-8">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <label for="name">Real Name:</label> <input type="text" name="name" id="name" placeholder="Real Name"><br>
  <label for="nick">Nick:</label> <input type="text" name="nick" id="nick" placeholder="local_nick"><br>
  <label for="homepage">Homepage:</label> <input type="text" name="homepage" id="homepage" placeholder="https://homepage.com"><br>
  <label for="twitter">Twitter Nick:</label> <input type="text" name="twitter" id="twitter" placeholder="Twitter handle"><br>
  <input type="submit" name="submit" value="Submit">
</form>
@stop