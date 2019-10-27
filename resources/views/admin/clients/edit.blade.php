@extends('master')

@section('title')Edit Client « Admin CP « @stop

@section('content')
    <h1>Edit Client</h1>
    <form action="/admin/clients/{{ $id }}" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div>
            <label for="client_url">Client URL:</label>
            <input type="text" name="client_url" id="client_url" value="{{ $client_url }}">
        </div>
        <div>
            <label for="client_name">Client Name:</label>
            <input type="text" name="client_name" id="client_name" value="{{ $client_name }}">
        </div>
        <div>
            <button type="submit" name="edit">Edit</button>
        </div>
    </form>
    <hr>
    <form action="/admin/clients/{{ $id }}" method="post">
        {{ csrf_field() }}
        {{ method_field('DELETE') }}
        <button type="submit" name="delete">Delete Client</button>
    </form>
@stop
