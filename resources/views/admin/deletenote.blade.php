@extends('master')

@section('title')
Delete Note « Admin CP
@stop

@section('content')
<form action="/admin/note/delete/{{ $id }}" method="post" accept-charset="utf-8">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset class="note-ui">
    <legend>Delete Note</legend>
    <p>Are you sure you want to delete the note?
    <label for="kludge"></label><input type="submit" value="Submit">
  </fieldset>
</form>
@stop
