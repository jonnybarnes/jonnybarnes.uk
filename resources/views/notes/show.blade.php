@extends('master')

@section('title'){{ strip_tags($note->getOriginal('note')) }} « Notes « @stop

@section('content')
            <div class="h-entry">
@include('templates.note', ['note' => $note])
@foreach($note->webmentions->filter(function ($webmention) {
    return ($webmention->type == 'in-reply-to');
}) as $reply)
                <div class="u-comment h-cite">
                    @if ($reply['author'])
                        <a class="u-author h-card mini-h-card" href="{{ $reply['author']['properties']['url'][0] }}">
                            @if (array_key_exists('photo', $reply['author']['properties']))
                                <img src="{{ $reply['author']['properties']['photo'][0] }}" alt="" class="photo u-photo logo">
                            @endif
                            <span class="fn">{{ $reply['author']['properties']['name'][0] }}</span>
                        </a>
                    @else
                        Unknown author
                    @endif
                    said at <a class="dt-published u-url" href="{{ $reply['source'] }}">{{ $reply['published'] }}</a>
                    <div class="e-content p-name">
                        {!! $reply['reply'] !!}
                    </div>
                </div>
@endforeach
@if($note->webmentions->filter(function ($webmention) {
    return ($webmention->type == 'like-of');
})->count() > 0)                <h1 class="notes-subtitle">Likes</h1>
@foreach($note->webmentions->filter(function ($webmention) {
    return ($webmention->type == 'like-of');
}) as $like)
                <a href="{{ $like['author']['properties']['url'][0] }}"><img src="{{ $like['author']['properties']['photo'][0] }}" alt="profile picture of {{ $like['author']['properties']['name'][0] }}" class="like-photo"></a>
@endforeach
@endif
@if($note->webmentions->filter(function ($webmention) {
    return ($webmention->type == 'repost-of');
})->count() > 0)                <h1 class="notes-subtitle">Reposts</h1>
@foreach($note->webmentions->filter(function ($webmention) {
    return ($webmention->type == 'repost-of');
}) as $repost)
                <p>
                    <a class="h-card vcard mini-h-card p-author" href="{{ $repost['author']['properties']['url'][0] }}">
                        <img src="{{ $repost['author']['properties']['photo'][0] }}" alt="profile picture of {{ $repost['author']['properties']['name'][0] }}" class="photo u-photo logo"> <span class="fn">{{ $repost['author']['properties']['name'][0] }}</span>
                    </a> reposted this at <a href="{{ $repost['source'] }}">{{ $repost['published'] }}</a>.
                </p>
@endforeach
@endif
                <!-- this empty tags are for https://brid.gy’s publishing service -->
                <a href="https://brid.gy/publish/twitter"></a>
            </div>
@stop

@section('scripts')
            <link rel="stylesheet" href="/assets/highlight/zenburn.css">
            @include('templates.mapbox-links')
@stop
