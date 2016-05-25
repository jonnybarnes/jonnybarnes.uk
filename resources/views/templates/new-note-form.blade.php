<form action="{{ $action }}" method="post" enctype="multipart/form-data" accept-charset="utf-8" id="{{ $id }}">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset class="note-ui">
  	<legend>New Note</legend>
    <label for="in-reply-to" accesskey="r">Reply-to: </label><input type="text" name="in-reply-to" id="in-reply-to" placeholder="in-reply-to-1 in-reply-to-2 …" value="{{ old('in-reply-to') }}">
    <label for="content" accesskey="n">Note: </label><textarea name="content" id="content" placeholder="Note" autofocus>{{ old('content') }}</textarea>
    <label for="webmentions" accesskey="w">Send webmentions: </label><input type="checkbox" name="webmentions" id="webmentions" checked="checked"><br>
@if ($micropub === true)
    <label for="syndication" accesskey="s">Syndication: </label>@if($syndication)<ul class="syndication-targets-list" name="syndication">@foreach($syndication as $target)<li><input type="checkbox" name="mp-syndicate-to[]" id="{{ $target }}" value="{{ $target }}" checked="checked"> <label for="{{ $target }}">{{ $target }}</label></li>@endforeach</ul>@endif
    <a href="/refresh-syndication-targets">Refresh Syndication Targets</a><br>
@endif
    <label for="photo" accesskey="p">Photo: </label><input type="file" accept="image/*" value="Upload" name="photo[]" id="photo" multiple>
    <label for="locate" accesskey="l"></label><button type="button" name="locate" id="locate" value="Locate" disabled>Locate</button>
    <label for="kludge"></label><button type="submit" name="submit" id="submit" value="Submit">Submit</button>
  </fieldset>
</form>
