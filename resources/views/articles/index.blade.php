@extends('master')

@section('title')Articles « @stop

@section('content')

@if (count($articles) == 0)
            <p>No articles exist for this time.</p>
@endif

@foreach ($articles as $article)
@if ($article->url != '')            <article class="link h-entry">@else            <article class="h-entry">@endif
                <header>
                    <h1 class="p-name">
@if($article->url == '')
                        <a href="{{ $article->link }}">{{ $article->title }}</a>
@else
                        <a href="{{ $article->url }}">{{ $article->title }}</a>
@endif
                    </h1>
                    <span class="post-info">Posted <time class="dt-published" title="{{ $article->tooltip_time }}" datetime="{{ $article->w3c_time }}">{{ $article->human_time }}</time> - <a title="Permalink" href="{{ $article->link }}">⚓</a></span>
                </header>
                <div class="e-content">
                    {!! $article->html !!}
                </div>
            </article>
@endforeach
            {{ $articles->links() }}
@stop

@section('scripts')
            <link rel="stylesheet" href="/assets/highlight/zenburn.css">
@stop
