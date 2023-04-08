<div class="h-entry">
    <p>
        <a class="u-bookmark-of<?php if ($bookmark->name !== null) { echo ' h-cite'; } ?>" href="{{ $bookmark->url }}">
            @isset($bookmark->name)
                {{ $bookmark->name }}
            @endisset

            @empty($bookmark->name)
                {{ $bookmark->url }}
            @endempty
        </a>
    </p>
    @isset($bookmark->content)
        <p>{{ $bookmark->content }}</p>
    @endisset
    @isset($bookmark->screenshot)
        <img class="screenshot" src="/assets/img/bookmarks/{{ $bookmark->screenshot }}.png">
    @endisset
    @isset($bookmark->archive)
        <p><a href="https://web.archive.org{{ $bookmark->archive }}">Internet Archive backup</a></p>
    @endisset
    @if(count($bookmark->tags) > 0)
        <ul class="tags">
            @foreach($bookmark->tags as $tag)
                <li><a href="/bookmarks/tagged/{{ $tag->tag }}" class="tag">{{ $tag->tag }}</a></li>
            @endforeach
        </ul>
    @endif
</div>
