@extends('master')

@section('title')New Client « Admin CP « @stop

@section('content')
            <h1>New Client</h1>
            <form action="/admin/clients/" method="post" accept-charset="utf-8">
                {{ csrf_field() }}
                <input type="text" name="client_url" id="client_url" placeholder="client_url"><br>
                <input type="text" name="client_name" id="client_name" placeholder="client_name"><br>
                <input type="submit" name="submit" value="Submit">
            </form>
@stop
