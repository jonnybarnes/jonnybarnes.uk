@extends('master')

@section('title')Edit Article « Admin CP « @stop

@section('content')
            <form action="/admin/blog/{{ $id }}" method="post" accept-charset="utf-8">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                <label for="title">Title (URL):</label>
                <br>
                <input type="text" name="title" id="title" value="{!! $post['0']['title'] !!}">
                <br>
                <input type="url" name="url" id="url" value="{!! $post['0']['url'] !!}">
                <br>
                <label for="main">Main:</label>
                <br>
                <textarea name="main" id="main">{{ $post['0']['main'] }}</textarea>
                <br>
                <label for="published">Published:</label><input type="checkbox" name="published" value="1"@if($post['0']['published'] == '1') checked="checked"@endif>
                <br>
                <input type="submit" name="save" value="Save">
            </form>
            <hr>
            <form action="/admin/blog/{{ $id }}" method="post">
                {{ csrf_field() }}
                {{ method_field('DELETE') }}
                <button type="submit" name="submit">
                    Delete
                </button>
            </form>
@stop
