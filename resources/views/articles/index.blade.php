@extends('master')

@section('title')Articles « @stop

@section('content')

@if (count($articles) == 0)
            <p>No articles exist for this time.</p>
@endif

@foreach ($articles as $article)
    @include('templates.article', ['article' => $article])
@endforeach
{{ $articles->links() }}
@stop
