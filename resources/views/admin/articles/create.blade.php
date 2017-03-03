@extends('master')

@section('title')
New Article « Admin CP
@stop

@section('content')
@if(isset($message))<p class="error">{{ $message }}</p>@endif
<form action="/admin/blog/" method="post" accept-charset="utf-8" enctype="multipart/form-data" id="newarticle">
{{ csrf_field() }}
<label for="title">Title (URL):</label>
<br>
<input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="Title here">
<br>
<input type="text" name="url" id="url" value="{{ old('url') }}" placeholder="Article URL">
<br>
<label for="main">Main:</label>
<br>
<textarea name="main" id="main" placeholder="Article here">{{ old('main') }}</textarea>
<br>
<label for="published">Published:</label><input type="checkbox" name="published" id="published" value="1">
<br>
<p>Or you can upload an <code>.md</code> file:</p><input type="file" accept=".md" name="article">
<br>
<button type="submit" name="save">Save</button>
</form>
@stop
