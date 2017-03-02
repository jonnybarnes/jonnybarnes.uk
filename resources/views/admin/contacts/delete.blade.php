@extends('master')

@section('title')
Delete Contact? « Admin CP
@stop

@section('content')
<form action="/admin/contacts/delete/{{ $id }}" method="post">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label for="delete">Are you sure you want to delete this contact? </label><input type="checkbox" name="delete" id="delete">
<br>
<input type="submit" id="submit" value="Submit">
</form>
@stop
