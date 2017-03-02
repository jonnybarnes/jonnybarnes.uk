@extends('master')

@section('title')
Delete Article? « Admin CP
@stop

@section('content')
<form action="/admin/blog/delete/{{ $id }}" method="post">
<label for="delete">Are you sure you want to delete this post? </label><input type="checkbox" name="delete" id="delete">
<br>
<input type="submit" id="submit" value="Submit">
</form>
@stop
