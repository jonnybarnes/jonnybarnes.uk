@extends('master')

@section('title')Edit Like « Admin CP « @stop

@section('content')
            <h1>Edit Like</h1>
            <form action="/admin/likes/{{ $id }}" method="post" accept-charset="utf-8" class="admin-form form">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                <div>
                    <label for="like_url">Like URL:</label>
                    <input type="text" name="like_url" id="like_url" value="{{ $like_url }}">
                </div>
                <div>
                    <button type="submit" name="edit">Edit</button>
                </div>
            </form>
            <hr>
            <form action="/admin/likes/{{ $id }}" method="post">
                {{ csrf_field() }}
                {{ method_field('DELETE') }}
                <button type="submit" name="delete">Delete Like</button>
            </form>
@stop
