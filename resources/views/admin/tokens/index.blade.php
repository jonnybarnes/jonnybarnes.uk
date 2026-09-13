@extends('master')

@section('title')List Tokens « Admin CP « @stop

@section('content')
    <h1>Micropub Tokens</h1>
    <p><a href="/admin/tokens/create">Generate new token</a></p>

    @if(session('new_token'))
        <div class="token-reveal">
            <p>Here's your new token. <strong>Copy it now</strong> — it won't be shown again.</p>
            <input type="text" readonly value="{{ session('new_token') }}" onclick="this.select()">
        </div>
    @endif

    @if($tokens->isEmpty())
        <p>No tokens have been issued.</p>
    @else
        <div class="full-bleed token-list-wrapper">
            <table class="token-list">
                <thead>
                    <tr>
                        <th scope="col">Client</th>
                        <th scope="col">Scope</th>
                        <th scope="col">Issued</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($tokens as $token)
                    <tr class="{{ $token->isRevoked ? 'is-revoked' : '' }}">
                        <td><a href="{{ $token->client_id }}" rel="noopener">{{ $token->client_id }}</a></td>
                        <td>
                            <span class="scope-chips">
                                @foreach(explode(' ', $token->scope) as $scope)
                                    <span class="scope-chip">{{ $scope }}</span>
                                @endforeach
                            </span>
                        </td>
                        <td><time datetime="{{ $token->created_at->toIso8601String() }}" title="{{ $token->created_at }}">{{ $token->created_at->diffForHumans() }}</time></td>
                        <td>
                            @if($token->isRevoked)
                                <span class="badge badge-revoked">Revoked {{ $token->revoked_at->diffForHumans() }}</span>
                            @else
                                <span class="badge badge-active"><span class="dot"></span>Active</span>
                            @endif
                        </td>
                        <td>
                            @unless($token->isRevoked)
                                <form action="/admin/tokens/{{ $token->id }}/revoke" method="post" onsubmit="return confirm('Revoke this token? This cannot be undone.')">
                                    {{ csrf_field() }}
                                    {{ method_field('PUT') }}
                                    <button type="submit" class="revoke" name="revoke">Revoke</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@stop
