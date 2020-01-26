@extends('master')

@section('title'){{ strip_tags($article->title) }} « @stop

@section('content')
    @include('templates.article', ['article' => $article])
@stop
