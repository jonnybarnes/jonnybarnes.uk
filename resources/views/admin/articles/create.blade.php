@extends('master')

@section('title')New Article « Admin CP « @stop

@section('content')
    @if(isset($message))
        <p class="error">{{ $message }}</p>
    @endif
    <form action="/admin/blog/" method="post" accept-charset="utf-8" enctype="multipart/form-data" class="admin-form form">
        {{ csrf_field() }}
        <div>
            <label for="title">Title (URL):</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="Title here">
            <input type="text" name="url" id="url" value="{{ old('url') }}" placeholder="Article URL">
        </div>
        <div>
            <label for="main">Main:</label>
            <textarea name="main" id="main" placeholder="Article here">{{ old('main') }}</textarea>
        </div>
        <div class="form-row">
            <label for="published">Published:</label>
            <input type="checkbox" name="published" id="published" value="1">
        </div>
        <p>Or you can upload an <code>.md</code> file:</p>
        <div>
            <input type="file" accept=".md" name="article">
        </div>
        <div>
            <button type="submit" name="save">Save</button>
        </div>
    </form>
@stop
