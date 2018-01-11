@extends('master')

@section('title')New Note « Admin CP « @stop

@section('content')
@if (count($errors) > 0)
            <div class="errors">
                <ul>
@foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
@endforeach
                </ul>
            </div>
@endif
            <form action="/admin/notes" method="post" accept-charset="utf-8" class="admin-form form">
                {{ csrf_field() }}
                <div>
                    <label for="in-reply-to" accesskey="r">Reply-to: </label>
                    <input type="text"
                           name="in-reply-to"
                           id="in-reply-to"
                           placeholder="in-reply-to-1 in-reply-to-2 …"
                    >
                </div>
                <div>
                    <label for="content" accesskey="n">Note: </label>
                    <textarea name="content"
                              id="content"
                              placeholder="Note"
                              autofocus="autofocus"
                    >{{ old('content') }}</textarea>
                </div>
                <div>
                    <button type="submit"
                            name="submit"
                    >Submit</button>
                </div>
            </form>
@stop
