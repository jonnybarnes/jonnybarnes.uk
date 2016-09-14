@extends('master')

@section('title')
{{ strip_tags($note->note) }} « Notes « Jonny Barnes
@stop

@section('content')
    <div class="h-entry">
      @include('templates.note', ['note' => $note])
@foreach($replies as $reply)
      <div class="reply p-comment h-cite">
        <a class="h-card vcard mini-h-card p-author" href="{{ $reply['url'] }}">
          <img src="{{ $reply['photo'] }}" alt="" class="photo u-photo logo"> <span class="fn">{{ $reply['name'] }}</span>
        </a> said at <a class="dt-published" href="{{ $reply['source'] }}">{{ $reply['date'] }}</a>
        <div class="e-content p-name">
          {!! $reply['reply'] !!}
        </div>
      </div>
@endforeach
    </div>
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
@stop

@section('scripts')
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css" integrity="sha384-ZDBUvY/seENyR1fE6u4p1oMFfsKVjIlkiB6TrCdXjeZVPlYanREcmZopTV8WFZ0q" crossorigin="anonymous">
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js" integrity="sha384-GgNQAMOzWcL0fePJqogHh8dCjsGKZpkBNgm3einGr0aUb9kcXvr9JeU/PDf5knja" crossorigin="anonymous"></script>

<script src="/assets/bower/Autolinker.min.js"></script>
<script src="/assets/js/links.js"></script>
<script src="/assets/js/maps.js"></script>

<script src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop
