<article class="h-entry @if ($article->url != '') link @endif">
    <header>
        <h1 class="p-name">
            <a href="{{ $article->url ?? $article->link }}">{{ $article->title }}</a>
        </h1>
        <span class="post-info">Posted <time class="dt-published" title="{{ $article->tooltip_time }}" datetime="{{ $article->w3c_time }}">{{ $article->human_time }}</time> - <a title="Permalink" href="{{ $article->link }}">⚓</a></span>
    </header>
    <div class="e-content">
        {!! $article->html !!}
    </div>
</article>
