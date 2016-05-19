@extends('master')
@section('title')Login @stop

@section('content')
<h2>Login</h2>
<form action="login" method="post">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <input type="text" name="username" placeholder="username">
  <input type="password" name="password" placeholder="password">
  <input type="submit" name="submit" value="Login">
</form>
@stop
