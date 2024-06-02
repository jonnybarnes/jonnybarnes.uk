@extends('master')

@section('title')IndieAuth « @stop

@section('content')
    <form class="indieauth" action="/auth/confirm" method="post">
        @csrf

        <input type="hidden" name="client_id" value="{{ $client_id }}">
        <input type="hidden" name="redirect_uri" value="{{ $redirect_uri }}">
        <input type="hidden" name="state" value="{{ $state }}">
        <input type="hidden" name="me" value="{{ $me }}">
        <input type="hidden" name="code_challenge" value="{{ $code_challenge }}">
        <input type="hidden" name="code_challenge_method" value="{{ $code_challenge_method }}">
        @if(!empty($scopes))
            @foreach($scopes as $scope)
                <input type="hidden" name="scope[]" value="{{ $scope }}">
            @endforeach
        @endif

        <h1>IndieAuth</h1>
        @if(!empty($error))
            <div class="error">{{ $error }}</div>
        @endif

        <p>You are attempting to log in with the client <code>{{ $client_id }}</code></p>
        <p>After approving the request you will be redirected to <code>{{ $redirect_uri }}</code></p>
        @if(!empty($scopes))
            <p>The client is requesting the following scopes:</p>
            <ul>
                @foreach($scopes as $scope)
                    <li>{{ $scope }}</li>
                @endforeach
            </ul>
        @endif

        <button type="submit">Approve</button>
    </form>
@stop
