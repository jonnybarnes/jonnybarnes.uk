@extends('master')

@section('title')Passkeys « Admin CP « @stop

@section('content')
    <h1>Passkeys</h1>
    @if(count($passkeys) > 0)
        <p>You have the following passkeys saved:</p>
        <ul>
            @foreach($passkeys as $passkey)
                <li>{{ $passkey->passkey_id }}</li>
            @endforeach
        </ul>
    @else
        <p>You have no passkey saved.</p>
    @endif
    <button type="button" class="add-passkey">Add Passkey</button>
@stop
