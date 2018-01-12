@extends('master')

@section('title')Likes « @stop

@section('content')
            <div class="h-feed">
@foreach($likes as $like)
                <div class="h-entry">
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
@isset($like->content)
                        <blockquote class="e-content">
                            {!! $like->content !!}
                        </blockquote>
@endisset
                    </div>
                </div>
@endforeach
            </div>
@stop
