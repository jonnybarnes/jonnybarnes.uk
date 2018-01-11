@extends('master')

@section('title')New Client « Admin CP « @stop

@section('content')
            <h1>New Client</h1>
            <form action="/admin/clients/" method="post" accept-charset="utf-8" class="admin-form form">
                {{ csrf_field() }}
                <div>
                    <label for="client_url">Client URL:</label>
                    <input type="text" name="client_url" id="client_url" placeholder="client_url">
                </div>
                <div>
                    <label for="client_name">Client Name:</label>
                    <input type="text" name="client_name" id="client_name" placeholder="client_name">
                </div>
                <div>
                    <button type="submit" name="submit">Submit</button>
                </div>
            </form>
@stop
