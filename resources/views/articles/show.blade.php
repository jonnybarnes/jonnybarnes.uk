@extends('master')

@section('title'){{ $article->published ? '' : '[Draft] ' }}{{ strip_tags($article->title) }} « @stop

@if (! $article->published)
    @push('head')
        <meta name="robots" content="noindex, nofollow">
    @endpush
@endif

@section('content')
    @unless ($article->published)
        <aside class="draft-notice" role="note">
            <strong>Draft</strong> — this post hasn’t been published yet. It isn’t listed on the site or in feeds, so only people with this link can see it.
        </aside>
    @endunless
    @include('templates.article', ['article' => $article])
@stop
