@extends('master')

@section('title')
Bookmark «
@stop

@section('content')
<div class="h-entry">
  <a class="u-bookmark-of<?php if ($bookmark->name !== null) { echo ' h-cite'; } ?>" href="{{ $bookmark->url }}">
    @isset($bookmark->name)
      {{ $bookmark->name }}
    @endisset

    @empty($bookmark->name)
      {{ $bookmark->url }}
    @endempty
  </a>
  @isset($bookmark->content)
  <p>{{ $bookmark->content }}</p>
  @endisset
  @isset($bookmark->screenshot)
  <img src="/assets/img/bookmarks/{{ $bookmark->screenshot }}.png">
  @endisset
  @isset($bookmark->archive)
  <p><a href="https://web.archive.org{{ $bookmark->archive }}">Internet Archive backup</a></p>
  @endisset
  @if(count($bookmark->tags) > 0)
  <ul>
    @foreach($bookmark->tags as $tag)
    <li><a href="/bookmarks/tagged/{{ $tag->tag }}">{{ $tag->tag }}</a></li>
    @endforeach
  </ul>
  @endif
  <!-- these empty tags are for https://brid.gy’s publishing service -->
  <a href="https://brid.gy/publish/twitter"></a>
  <a href="https://brid.gy/publish/facebook"></a>
</div>
@stop
