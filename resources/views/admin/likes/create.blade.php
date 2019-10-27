@extends('master')

@section('title')New Like « Admin CP « @stop

@section('content')
    <h1>New Like</h1>
    <form action="/admin/likes/" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        <div>
            <label for="like_url">Like URL:</label>
            <input type="text" name="like_url" id="like_url" placeholder="Like URL">
        </div>
        <div>
            <button type="submit" name="submit">Submit</button>
        </div>
    </form>
@stop
