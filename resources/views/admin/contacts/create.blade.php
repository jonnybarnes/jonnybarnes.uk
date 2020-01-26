@extends('master')

@section('title')New Contact « Admin CP « @stop

@section('content')
    <h1>New Contact</h1>
    <form action="/admin/contacts/" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        <div>
            <label for="name">Real Name:</label>
            <input type="text" name="name" id="name" placeholder="Real Name">
        </div>
        <div>
            <label for="nick">Nick:</label>
            <input type="text" name="nick" id="nick" placeholder="local_nick">
        </div>
        <div>
            <label for="homepage">Homepage:</label>
            <input type="text" name="homepage" id="homepage" placeholder="https://homepage.com">
        </div>
        <div>
            <label for="twitter">Twitter Nick:</label>
            <input type="text" name="twitter" id="twitter" placeholder="Twitter handle">
        </div>
        <div>
            <button type="submit" name="submit">Submit</button>
        </div>
    </form>
@stop
