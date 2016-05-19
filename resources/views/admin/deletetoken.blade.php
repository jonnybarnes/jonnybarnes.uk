@extends('master')

@section('title')
Delete Token? « Admin CP
@stop

@section('content')
<form action="/admin/tokens/delete/{{ $id }}" method="post">
<label for="delete">Are you sure you want to delete this token? </label><input type="checkbox" name="delete" id="delete">
<br>
<input type="submit" id="submit" value="Submit">
</form>
@stop
