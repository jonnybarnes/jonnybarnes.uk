<div class="h-entry">
    @if ($note->twitter)
        {!! $note->twitter->html !!}
    @elseif ($note->in_reply_to)
        <div class="u-in-reply-to h-cite reply-to">
            In reply to <a href="{{ $note->in_reply_to }}" class="u-url">{{ $note->in_reply_to }}</a>
        </div>
    @endif
    <div class="note">
        <div class="e-content p-name">
            {!! $note->note !!}
            @foreach($note->media as $media)
                @if($media->type === 'image')
                    <a class="naked-link" href="{{ $media->url }}">
                        <img class="u-photo" src="{{ $media->url }}" alt="" @if($media->image_widths !== null) srcset="{{ $media->url }} {{ $media->image_widths }}w, {{ $media->mediumurl }} 1000w, {{ $media->smallurl }} 500w" sizes="80vh"@endif>
                    </a>
                @endif
                @if($media->type === 'audio')
                    <audio class="u-audio" src="{{ $media->url }}" controls>
                @endif
                @if($media->type === 'video')
                    <video class="u-video" src="{{ $media->url }}" controls>
                @endif
                @if($media->type === 'download')
                    <p><a class="u-attachment" href="{{ $media->url }}">Download the attached media</a></p>
                @endif
            @endforeach
        </div>
        <div class="note-metadata">
            <div>
                <a class="u-url" href="/notes/{{ $note->nb60id }}">
                    <time class="dt-published" datetime="{{ $note->iso8601 }}" title="{{ $note->iso8601 }}">{{ $note->humandiff }}</time>
                </a>
                @if($note->client) via <a class="client" href="{{ $note->client->client_url }}">{{ $note->client->client_name }}</a>@endif
                @if($note->place)
                    @if($note->getOriginal('note'))
                        in <span class="p-location h-card"><a class="p-name u-url" href="{{ $note->place->longurl }}">{{ $note->address }}</a><data class="p-latitude" value="{{ $note->place->latitude }}"></data><data class="p-longitude" value="{{ $note->place->longitude }}"></data></span>
                    @endif
                @elseif($note->address)
                    in <span class="p-location h-adr">{!! $note->address !!}<data class="p-latitude" value="{{ $note->latitude }}"></data><data class="p-longitude" value="{{ $note->longitude }}"></data></span>
                @endif
            </div>
            @if($note->replies > 0 || $note->likes > 0 || $note->reposts > 0)
                <div class="webmention-info">
                    @if($note->replies > 0)
                        <div class="replies">
                            @include('icons.reply')
                            {{ $note->replies }}
                            <span class="sr-only">replies</span>
                        </div>
                    @endif
                    @if($note->likes > 0)
                        <div class="likes">
                            @include('icons.like')
                            {{ $note->likes }}
                            <span class="sr-only">likes</span>
                        </div>
                    @endif
                    @if($note->reposts > 0)
                        <div class="reposts">
                            @include('icons.repost')
                            {{ $note->reposts }}
                            <span class="sr-only">reposts</span>
                        </div>
                    @endif
                </div>
            @endif
            <div class="syndication-links">
                @if(
                    $note->tweet_id ||
                    $note->facebook_url ||
                    $note->swarm_url ||
                    $note->instagram_url ||
                    $note->mastodon_url
                )
                    @include('templates.social-links', [
                        'tweet_id' => $note->tweet_id,
                        'facebook_url' => $note->facebook_url,
                        'swarm_url' => $note->swarm_url,
                        'instagram_url' => $note->instagram_url,
                        'mastodon_url' => $note->mastodon_url,
                    ])
                @endif
            </div>
        </div>
        @if ($note->place)
            <div class="map"
                data-latitude="{{ $note->place->latitude }}"
                data-longitude="{{ $note->place->longitude }}"
                data-name="{{ $note->place->name }}"
                data-marker="{{ $note->place->icon }}"
            ></div>
        @endif
    </div>
</div>
