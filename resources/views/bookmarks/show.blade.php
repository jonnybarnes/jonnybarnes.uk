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
</div>
@stop
