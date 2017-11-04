@extends('master')

@section('title')Bookmarks « @stop

@section('content')
            <div class="h-feed top-space">
@foreach($bookmarks as $bookmark)
                <div class="h-entry">
                    <a class="u-bookmark-of<?php if ($bookmark->name !== null) { echo ' h-cite'; } ?>" href="{{ $bookmark->url }}">
@isset($bookmark->name)
                        {{ $bookmark->name }}
@endisset

@empty($bookmark->name)
                        {{ $bookmark->url }}
@endempty
                    </a> &nbsp; <a href="/bookmarks/{{ $bookmark->id }}">🔗</a>
@isset($bookmark->content)
                    <p>{{ $bookmark->content }}</p>
@endisset
@isset($bookmark->screenshot)
                    <img class="screenshot" src="/assets/img/bookmarks/{{ $bookmark->screenshot }}.png">
@endisset
@isset($bookmark->archive)
                    <p><a href="https://web.archive.org{{ $bookmark->archive }}">Internet Archive backup</a></p>
@endisset
@if($bookmark->tags_count > 0)
                    <ul class="tags">
@foreach($bookmark->tags as $tag)
                        <li><a href="/bookmarks/tagged/{{ $tag->tag }}" class="tag">{{ $tag->tag }}</a></li>
@endforeach
                    </ul>
@endif
                </div>
@endforeach
            </div>

            {{ $bookmarks->links() }}
@stop
