@extends('master')

@section('title')Edit Article « Admin CP « @stop

@section('content')
    <form action="/admin/blog/{{ $article->id }}" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div>
            <label for="title">Title (URL):</label>
            <input type="text" name="title" id="title" value="{!! $article->title !!}">
            <input type="url" name="url" id="url" value="{!! $article->url !!}">
        </div>
        <div>
            <label for="main">Main:</label>
            <textarea name="main" id="main">{!! $article->main !!}</textarea>
        </div>
        <div class="form-row">
            <label for="published">Published:</label>
            <input type="checkbox" name="published" value="1"@if($article->published == '1') checked="checked"@endif>
        </div>
        <div>
            <button type="submit" name="save">Save</button>
        </div>
    </form>
    <hr>
    <form action="/admin/blog/{{ $article->id }}" method="post" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('DELETE') }}
        <div>
            <button type="submit" name="delete">Delete</button>
        </div>
    </form>
@stop
