@extends('master')

@section('title')
{{ strip_tags($note->note) }} « Notes «
@stop

@section('content')
    <div class="h-entry">
      @include('templates.note', ['note' => $note])
@foreach($replies as $reply)
      <div class="u-comment h-cite">
        <a class="u-author h-card mini-h-card" href="{{ $reply['url'] }}">
          <img src="{{ $reply['photo'] }}" alt="" class="photo u-photo logo"> <span class="fn">{{ $reply['name'] }}</span>
        </a> said at <a class="dt-published u-url" href="{{ $reply['source'] }}">{{ $reply['date'] }}</a>
        <div class="e-content p-name">
          {!! $reply['reply'] !!}
        </div>
      </div>
@endforeach
@if(count($likes) > 0)<h1 class="notes-subtitle">Likes</h1>@endif
@foreach($likes as $like)
    <a href="{{ $like['url'] }}"><img src="{{ $like['photo'] }}" alt="profile picture of {{ $like['name'] }}" class="like-photo"></a>
@endforeach
@if(count($reposts) > 0)<h1 class="notes-subtitle">Reposts</h1>@endif
@foreach($reposts as $repost)
<p><a class="h-card vcard mini-h-card p-author" href="{{ $repost['url'] }}">
    <img src="{{ $repost['photo'] }}" alt="profile picture of {{ $repost['name'] }}" class="photo u-photo logo"> <span class="fn">{{ $repost['name'] }}</span>
</a> reposted this at <a href="{{ $repost['source'] }}">{{ $repost['date'] }}</a>.</p>
@endforeach
      <!-- these empty tags are for https://brid.gy’s publishing service -->
      <a href="https://brid.gy/publish/twitter"></a>
      <a href="https://brid.gy/publish/facebook"></a>
    </div>
@stop

@section('scripts')

<script defer src="/assets/js/links.js"></script>
<link rel="stylesheet" href="/assets/frontend/mapbox-gl.css">
<script defer src="/assets/js/maps.js"></script>

<script src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop
