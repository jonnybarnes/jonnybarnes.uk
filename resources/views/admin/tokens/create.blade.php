@extends('master')

@section('title')New Token « Admin CP « @stop

@section('content')
    <h1>Generate a new token</h1>
    <p>Use this for clients that can't complete the IndieAuth authorization flow (e.g. they don't support PKCE) and instead let you paste in a token directly.</p>

    <form action="/admin/tokens" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}

        <div>
            <label for="client_id">Client</label>
            <input
                type="text"
                name="client_id"
                id="client_id"
                value="{{ old('client_id') }}"
                placeholder="https://ia.net/writer"
                required
            >
        </div>

        <div class="scope-checkboxes">
            <span>Scope</span>
            <label for="scope_create">
                <input
                    type="checkbox"
                    name="scope[]"
                    id="scope_create"
                    value="create"
                    @checked(in_array('create', old('scope', []), true))
                >
                create
            </label>
            <label for="scope_update">
                <input
                    type="checkbox"
                    name="scope[]"
                    id="scope_update"
                    value="update"
                    @checked(in_array('update', old('scope', []), true))
                >
                update
            </label>
        </div>

        <div>
            <button type="submit" name="save">Generate token</button>
        </div>
    </form>
@stop
