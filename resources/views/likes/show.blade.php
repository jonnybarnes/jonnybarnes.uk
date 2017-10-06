@extends('master')

@section('title')
Like «
@stop

@section('content')
<div class="h-entry">
  <div class="h-cite u-like-of">
    Liked <a class="u-url" href="{{ $like->url }}">a post</a> by
    <span class="p-author h-card">
      @isset($like->author_url)
      <a class="u-url p-name" href="{{ $like->author_url }}">{{ $like->author_name }}</a>
      @else
      <span class="p-name">{{ $like->author_name }}</span>
      @endisset
    </span>:
    <blockquote class="e-content">
      {!! $like->content !!}
    </blockquote>
  </div>
</div>
@stop
