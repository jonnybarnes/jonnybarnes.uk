@extends('master')

@section('title')List Tokens « Admin CP « @stop

@section('content')
    <h1>Micropub Tokens</h1>
    @if($tokens->isEmpty())
        <p>No tokens have been issued.</p>
    @else
        <ul>
        @foreach($tokens as $token)
            <li>
                {{ $token->client_id }} — scope: {{ $token->scope }} — issued {{ $token->created_at->diffForHumans() }}
                @if($token->isRevoked)
                    — revoked {{ $token->revoked_at->diffForHumans() }}
                @else
                    <form action="/admin/tokens/{{ $token->id }}/revoke" method="post">
                        {{ csrf_field() }}
                        {{ method_field('PUT') }}
                        <button type="submit" name="revoke">Revoke</button>
                    </form>
                @endif
            </li>
        @endforeach
        </ul>
    @endif
@stop
