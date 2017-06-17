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
      @foreach($note->media()->get() as $media)
        @if($media->type == 'image')<img class="u-photo" src="{{ $media->url }}" alt="">@endif
        @if($media->type == 'audio')<audio class="u-audio" src="{{ $media->url }}" controls>@endif
        @if($media->type == 'video')<video class="u-video" src="{{ $media->url }}" controls>@endif
        @if($media->type == 'download')<p><a class="u-attachment" href="{{ $media->url }}">Download the attached media</a></p>@endif
      @endforeach
    </div>
    <div class="note-metadata">
      <div>
        <a class="u-url" href="/notes/{{ $note->nb60id }}"><time class="dt-published" datetime="{{ $note->iso8601_time }}">{{ $note->human_time }}</time></a>@if($note->client_name) via <a class="client" href="{{ $note->client_id }}">{{ $note->client_name }}</a>@endif
        @if($note->placeLink)in <span class="p-location h-card"><a class="p-name u-url" href="{{ $note->placeLink }}">{{ $note->address }}</a><data class="p-latitude" value="{{ $note->latitude }}"></data><data class="p-longitude" value="{{ $note->longitude }}"></data></span>
        @elseif($note->address)in <span class="p-location h-adr">{!! $note->address !!}<data class="p-latitude" value="{{ $note->latitude }}"></data><data class="p-longitude" value="{{ $note->longitude }}"></data></span>@endif
        @if($note->replies > 0) @include('templates.replies-icon'): {{ $note->replies }}@endif
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
@if ($note->placeLink)
    <div class="map"
        data-latitude="{{ $note->latitude }}"
        data-longitude="{{ $note->longitude }}"
        data-name="{{ $note->place->name }}"
        data-marker="{{ $note->place->icon }}"></div>
@endif
  </div>
