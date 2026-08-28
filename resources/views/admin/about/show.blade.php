@extends('master')

@section('title')Edit About « Admin CP « @stop

@section('content')
    <h1>Edit About</h1>
    <form action="/admin/about" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div>
            <label for="content">Content:</label>
            <br>
            <textarea name="content" id="content" rows="10" cols="50">{{ old('content', $aboutEntry?->content) }}</textarea>
        </div>
        <div>
            <button type="submit" name="save">Save</button>
        </div>
    </form>
@stop
