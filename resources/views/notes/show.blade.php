@extends('master')

@section('title'){{ strip_tags($note->getOriginal('note')) }} « Notes « @stop

@section('content')
@include('templates.note', ['note' => $note])
@foreach($note->webmentions->filter(function ($webmention) {
    return ($webmention->type == 'in-reply-to');
}) as $reply)
                <div class="u-comment h-cite">
                    @if ($reply['author'])
                        @php($replyAuthorUrl = $reply['author']['properties']['url'][0] ?? null)
                        @if (is_string($replyAuthorUrl))
                        <a class="u-author h-card mini-h-card" href="{{ $replyAuthorUrl }}">
                        @else
                        <span class="u-author h-card mini-h-card">
                        @endif
                            @if (isset($reply['author']['properties']['photo'][0]))
                                <img src="{{ $reply['author']['properties']['photo'][0] }}" alt="" class="photo u-photo logo">
                            @endif
                            <span class="fn">{{ $reply['author']['properties']['name'][0] ?? $reply['author']['properties']['nickname'][0] ?? 'unknown' }}</span>
                        @if (is_string($replyAuthorUrl))
                        </a>
                        @else
                        </span>
                        @endif
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
    return ($webmention->type === 'like-of');
})->count() > 0)
    <h1 class="notes-subtitle">Likes</h1>
    <div class="webmentions-author-list">
        @foreach($note->webmentions->filter(function ($webmention) {
            return ($webmention->type === 'like-of');
        }) as $like)
            @php($likeAuthor = $like['author']['properties'] ?? [])
            <a href="{{ is_string($likeAuthor['url'][0] ?? null) ? $likeAuthor['url'][0] : $like['source'] }}">
                <img src="{{ $likeAuthor['photo'][0] ?? '/assets/profile-images/default-image' }}" alt="profile picture of {{ $likeAuthor['name'][0] ?? $likeAuthor['nickname'][0] ?? 'unknown' }}" class="like-photo">
            </a>
        @endforeach
    </div>
@endif
@if($note->webmentions->filter(function ($webmention) {
    return ($webmention->type === 'repost-of');
})->count() > 0)
    <h1 class="notes-subtitle">Reposts</h1>
    <div class="webmentions-author-list">
        @foreach($note->webmentions->filter(function ($webmention) {
            return ($webmention->type == 'repost-of');
        }) as $repost)
            <a href="{{ $repost['source'] }}">
                @php($repostAuthor = $repost['author']['properties'] ?? [])
                <img src="{{ $repostAuthor['photo'][0] ?? '/assets/profile-images/default-image' }}" alt="{{ $repostAuthor['name'][0] ?? $repostAuthor['nickname'][0] ?? 'unknown' }} reposted this at {{ $repost['published'] }}">
            </a>
        @endforeach
    </div>
@endif
@stop

@section('scripts')
    @parent
    <link rel="stylesheet" href="/assets/highlight/nord.css">
@stop
