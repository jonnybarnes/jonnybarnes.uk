@if ($note->twitter)
  {!! $note->twitter->html !!}
@elseif ($note->in_reply_to)
  <div class="p-in-reply-to h-cite reply-to">
    In reply to <a href="{{ $note->reply_to }}" class="u-url">{{ $note->in_reply_to }}</a>
  </div>
@endif
  <div class="note">
    <div class="e-content p-name">
      {!! $note->note !!}
      @if(count($note->photoURLs) > 0)
        @foreach($note->photoURLs as $photoURL)
          <img src="{{ $photoURL }}" alt="" class="note-photo u-photo">
        @endforeach
      @endif
    </div>
    <div class="note-metadata">
      <a class="u-url" href="/notes/{{ $note->nb60id }}"><time class="dt-published" datetime="{{ $note->iso8601_time }}">{{ $note->human_time }}</time></a>@if($note->client_name) via <a class="client" href="{{ $note->client_id }}">{{ $note->client_name }}</a>@endif
      @if($note->address)in <span class="p-location"><span class="p-name">@if($note->placeLink)<a href="{{ $note->placeLink }}">@endif{{ $note->address }}@if($note->placeLink)</a>@endif</span><data class="p-latitude" value="{{ $note->latitude }}"><data class="p-longitude" value="{{ $note->longitude }}"></span>@endif
      @if($note->replies > 0)Replies: {{ $note->replies }}@endif
      @if($note->tweet_id)@include('templates.social-links', ['tweet_id' => $note->tweet_id, 'nb60id' => $note->nb60id])@endif
@if ($note->placeLink)
      <div class="map" data-latitude="{{ $note->latitude }}" data-longitude="{{ $note->longitude }}"></div>
@endif
    </div>
  </div>
