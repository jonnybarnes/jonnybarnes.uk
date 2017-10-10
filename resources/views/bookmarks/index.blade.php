@extends('master')

@section('title')
Bookmarks «
@stop

@section('content')
<div class="h-feed">
@foreach($bookmarks as $bookmark)
  <div class="h-entry">
    <a class="u-bookmark-of<?php if ($bookmark->name !== null) { echo ' h-cite'; } ?>" href="{{ $bookmark->url }}">
      @isset($bookmark->name)
        {{ $bookmark->name }}
      @endisset

      @empty($bookmark->name)
        {{ $bookmark->url }}
      @endempty
    </a>
  </div>
@endforeach
</div>
@stop
