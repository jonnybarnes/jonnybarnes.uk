@extends('master')

@section('title')
Edit Note « Admin CP
@stop

@section('content')
<form action="/admin/note/edit/{{ $id }}" method="post" accept-charset="utf-8">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset class="note-ui">
    <legend>Edit Note</legend>
    <label for="in-reply-to" accesskey="r">Reply-to: </label><input type="text" name="in-reply-to" id="in-reply-to" placeholder="in-reply-to-1 in-reply-to-2 …" tabindex="1" value="{{ $note->in_reply_to }}"><br>
    <label for="content" accesskey="n">Note: </label><textarea name="content" id="content" placeholder="Note" tabindex="2">{{ $note->originalNote }}</textarea><br>
    <label for="webmentions" accesskey="w">Send webmentions: </label><input type="checkbox" name="webmentions" id="webmentions" checked="checked" tabindex="3"><br>
    <label for="kludge"></label><input type="submit" value="Submit" tabindex="6"><br>
  </fieldset>
</form>
@stop
