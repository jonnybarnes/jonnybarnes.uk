@extends('master')

@section('title')Like « @stop

@section('content')
            <div class="h-entry top-space">
                <div class="h-cite u-like-of">
                    Liked <a class="u-url" href="{{ $like->url }}">a post</a>
@isset($like->author_name)
                    by <span class="p-author h-card">
@isset($like->author_url)
                        <a class="u-url p-name" href="{{ $like->author_url }}">{{ $like->author_name }}</a>
@else
                        <span class="p-name">{{ $like->author_name }}</span>
@endisset
                    </span>
@endisset
                    <blockquote class="e-content">
                        {!! $like->content !!}
                    </blockquote>
                </div>
            </div>

            <!-- POSSE to Twitter -->
            <a href="https://brid.gy/publish/twitter"></a>
@stop
