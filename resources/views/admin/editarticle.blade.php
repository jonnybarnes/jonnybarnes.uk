@extends('master')

@section('title')
Edit Article « Admin CP
@stop

@section('content')
<form action="/admin/blog/edit/{{ $id }}" method="post" accept-charset="utf-8">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
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
@stop
