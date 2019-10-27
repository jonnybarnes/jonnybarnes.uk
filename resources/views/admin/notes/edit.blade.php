@extends('master')

@section('title')Edit Note « Admin CP « @stop

@section('content')
    <form action="/admin/notes/{{ $note->id }}" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div>
            <label for="in-reply-to" accesskey="r">Reply-to: </label>
            <input type="text" name="in-reply-to" id="in-reply-to" placeholder="in-reply-to-1 in-reply-to-2 …" tabindex="1" value="{{ $note->in_reply_to }}">
        </div>
        <div>
            <label for="content" accesskey="n">Note: </label>
            <textarea name="content" id="content" placeholder="Note" tabindex="2">{{ $note->originalNote }}</textarea>
        </div>
        <div class="form-row">
            <label for="webmentions" accesskey="w">Send webmentions: </label>
            <input type="checkbox" name="webmentions" id="webmentions" checked="checked" tabindex="3">
        </div>
        <div>
            <button type="submit" name="submit">Submit</button>
        </div>
    </form>
    <hr>
    <form action="/admin/notes/{{ $note->id }}" method="post" accept-charset="utf-8" class="form">
        {{ csrf_field() }}
        {{ method_field('DELETE') }}
        <div>
            <button type="submit" name="delete">Delete</button>
        </div>
    </form>
@stop
