<div class="h-entry">
    <div class="h-cite u-like-of">
        <p>
            Liked <a class="u-url" href="{{ $like->url }}">a post</a>
        </p>
        @isset($like->author_name)
            <div>
                by <span class="p-author h-card">
                @isset($like->author_url)
                    <a class="u-url p-name" href="{{ $like->author_url }}">{{ $like->author_name }}</a>
                @else
                    <span class="p-name">{{ $like->author_name }}</span>
                @endisset
                </span>
            </div>
        @endisset
        @isset($like->content)
            <blockquote class="e-content">
                {!! $like->content !!}
            </blockquote>
        @endisset
        <div>
            <a href="/likes/{{ $like->id }}"><time class="dt-published" datetime="{{ $like->updated_at->toISO8601String() }}" title="{{ $like->updated_at->toISO8601String() }}">{{ $like->updated_at->diffForHumans() }}</time></a>
        </div>
    </div>
</div>
