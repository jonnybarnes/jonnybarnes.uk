@extends('master')
@section('title')Login @stop

@section('content')
            <h2>Login</h2>
            <form action="{{ route('login') }}" method="post">
                @csrf
                <input type="text" name="name" placeholder="username">
                <input type="password" name="password" placeholder="password">
                <input type="submit" name="submit" value="Login">
            </form>
            <p><button type="button" class="login-passkey">Login with Passkeys</button></p>
@stop
