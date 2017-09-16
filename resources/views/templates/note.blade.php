@if ($note->twitter)
  {!! $note->twitter->html !!}
@elseif ($note->in_reply_to)
  <div class="p-in-reply-to h-cite reply-to">
    In reply to <a href="{{ $note->in_reply_to }}" class="u-url">{{ $note->in_reply_to }}</a>
  </div>
@endif
  <div class="note">
    <div class="e-content p-name">
      {!! $note->note !!}
      @foreach($note->media as $media)
        @if($media->type == 'image')<a class="naked-link" href="{{ $media->url }}"><img class="u-photo" src="{{ $media->url }}" alt="" @if($media->image_widths !== null) srcset="{{ $media->url }} {{ $media->image_widths }}w, {{ $media->mediumurl }} 1000w, {{ $media->smallurl }} 500w" sizes="80vh"@endif></a>@endif
        @if($media->type == 'audio')<audio class="u-audio" src="{{ $media->url }}" controls>@endif
        @if($media->type == 'video')<video class="u-video" src="{{ $media->url }}" controls>@endif
        @if($media->type == 'download')<p><a class="u-attachment" href="{{ $media->url }}">Download the attached media</a></p>@endif
      @endforeach
    </div>
    @if($note->twitter_content)<div class="p-bridgy-twitter-content">
      {!! $note->twitter_content !!}
    </div>@endif
    @if($note->facebook_content)<div class="p-bridgy-facebook-content">
      {!! $note->facebook_content !!}
    </div>@endif
    <div class="note-metadata">
      <div>
        <a class="u-url" href="/notes/{{ $note->nb60id }}"><time class="dt-published" datetime="{{ $note->iso8601 }}" title="{{ $note->iso8601 }}">{{ $note->humandiff }}</time></a>@if($note->client) via <a class="client" href="{{ $note->client->client_url }}">{{ $note->client->client_name }}</a>@endif
        @if($note->place)in <span class="p-location h-card"><a class="p-name u-url" href="{{ $note->place->longurl }}">{{ $note->address }}</a><data class="p-latitude" value="{{ $note->place->latitude }}"></data><data class="p-longitude" value="{{ $note->place->longitude }}"></data></span>
        @elseif($note->address)in <span class="p-location h-adr">{!! $note->address !!}<data class="p-latitude" value="{{ $note->latitude }}"></data><data class="p-longitude" value="{{ $note->longitude }}"></data></span>@endif
        @if($note->replies_count > 0) @include('templates.replies-icon'): {{ $note->replies_count }}@endif
      </div>
      <div class="social-links">
        @if(
            $note->tweet_id ||
            $note->facebook_url ||
            $note->swarm_url ||
            $note->instagram_url)
            @include('templates.social-links', [
                'tweet_id' => $note->tweet_id,
                'facebook_url' => $note->facebook_url,
                'swarm_url' => $note->swarm_url,
                'instagram_url' => $note->instagram_url,
            ])
        @endif
      </div>
    </div>
@if ($note->place)
    <div class="map"
        data-latitude="{{ $note->place->latitude }}"
        data-longitude="{{ $note->place->longitude }}"
        data-name="{{ $note->place->name }}"
        data-marker="{{ $note->place->icon }}"></div>
@endif
  </div>
