<form action="{{ $action }}" method="post" enctype="multipart/form-data" accept-charset="utf-8"@if($micropub) name="micropub"@endif>
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset class="note-ui">
  	<legend>New Note</legend>
    <div>
      <label for="in-reply-to" accesskey="r">Reply-to: </label>
      <input type="text"
             name="in-reply-to"
             id="in-reply-to"
             placeholder="in-reply-to-1 in-reply-to-2 …"
             value="{{ old('in-reply-to') }}"
      >
    </div>
    <div>
      <label for="content" accesskey="n">Note: </label>
      <textarea name="content"
                id="content"
                placeholder="Note"
                autofocus="autofocus">{{ old('content') }}</textarea>
    </div>
@if ($micropub === true)
  @if($syndication)
    <div>
      <label for="syndication" accesskey="s">Syndication: </label>
      <ul id="syndication">
        @foreach($syndication as $syn)
        <li><input type="checkbox"
                   name="mp-syndicate-to[]"
                   id="{{ $syn['target'] }}"
                   value="{{ $syn['target'] }}"
                   checked="checked"
            > <label for="{{ $syn['target'] }}">{{ $syn['name'] }}</label>
        </li>
        @endforeach
      </ul>
    </div>
  @endif
  @if($mediaURLs)
    <div class="mp-media">
      <label for="media">Media:</label>
      <ul>
    @foreach($mediaURLs as $mediaURL)
        <li>
          <input type="checkbox" name="media[]" id="{{ $mediaURL }}" value="{{ $mediaURL }}" checked>
          <label for="{{ $mediaURL }}"><img src="{{ $mediaURL }}" alt=""></label>
        </li>
    @endforeach
      </ul>
    </div>
    <div>
      <label for="kludge"></label>
      <a href="/micropub/media/clearlinks">Clear media</a>
    </div>
  @endif
@endif
@if(!$mediaEndpoint)
    <div>
      <label for="photo" accesskey="p">Photo: </label>
      <input type="file"
             accept="image/*"
             value="Upload"
             name="photo[]"
             id="photo"
             multiple
      >
    </div>
@endif
    <div>
      <label for="locate" accesskey="l"></label>
      <button type="button"
              name="locate"
              id="locate"
              value="Locate"
              disabled
      >Locate</button>
      <button type="submit"
              name="submit"
              id="submit"
              value="Submit"
      >Submit</button>
    </div>
  </fieldset>
</form>

@if($mediaEndpoint)
  <form action="{{ route('process-media') }}" method="post" enctype="multipart/form-data" accept-charset="utf8" name="media-upload">
    {{ csrf_field() }}
    <fieldset class="note-ui">
      <legend>Media Upload</legend>
      <div>
        <label for="media" accesskey="m">Media: </label>
        <input type="file"
               accept="audio/*,video/*,image/*,application/pdf,.md"
               value="Upload"
               name="file[]"
               id="media"
               multiple
        >
      </div>
      <div>
        <label for="kludge"></label>
        <button type="submit" name="upload-media" id="upload-media">Upload Media</button>
      </div>
    </fieldset>
  </form>
@endif
