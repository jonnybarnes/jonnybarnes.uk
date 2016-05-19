@extends('master')

@section('title')
New Article « Admin CP
@stop

@section('content')
@if(isset($message))<p class="error">{{ $message }}</p>@endif
<form action="/admin/blog/new" method="post" accept-charset="utf-8" enctype="multipart/form-data" id="newarticle">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
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
<h2>Preview</h2>
@stop

@section('scripts')
@parent
<script src="{{ elixir('assets/js/marked.min.js') }}"></script>
<script>
  var preview = document.createElement('div');
  preview.classList.add('preview');
  var main = document.querySelector('main');
  main.appendChild(preview);
  var textarea = document.querySelector('textarea');
  window.setInterval(function () {
    var markdown = textarea.value;
    preview.innerHTML = marked(markdown);
  }, 5000);
</script>
<script src="{{ elixir('assets/js/store2.min.js') }}"></script>
<script src="{{ elixir('assets/js/alertify.js') }}"></script>
<script src="{{ elixir('assets/js/form-save.js') }}"></script>

<link rel="stylesheet" href="{{ elixir('assets/css/alertify.css') }}">
@stop
