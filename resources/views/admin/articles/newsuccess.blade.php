@extends('master')

@section('title')
New Article Success « Admin CP
@stop

@section('content')
<p>Successfully created article with id: {{ $id }}, title: {{ $title }}</p>
@stop
