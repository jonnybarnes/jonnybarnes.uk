@extends('master')

@section('title')
New Note «
@stop

@section('content')
<p>This is my UI for posting new notes, hopefully you’ll soon be able to use this if your site supports the micropub API.</p>
@if($errors->endpoint->first() != '')
<p class="error">{{ $errors->endpoint->first() }}</p>
@endif
@if($errors->indieauth->first() != '')
<p class="error">{{ $errors->indieauth->first() }}</p>
@endif
@if($url === null)
<form action="/beginauth" method="post" id="login">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset>
    <legend>IndieAuth</legend>
    <label for="indie_auth_url" accesskey="a">Web Address: </label><input type="text" name="me" id="indie_auth_url" placeholder="yourdomain.com">
    <label for="kludge"></label><button type="submit" name="sign-in" id="sign-in" value="Sign in">Sign in</button>
  </fieldset>
</form>
@else
<p>You are authenticated as <code>{{ $url }}</code>, <a href="/logout">log out</a>.</p>
@endif
  @include('templates.new-note-form', [
    'micropub' => true,
    'action' => route('micropub-client-post')
  ])
@stop

@section('scripts')
<script>
    window.Promise || document.write('<script src="https://unpkg.com/promise-polyfill/promise.min.js"><\/script>');
    window.fetch || document.write('<script src="https://unpkg.com/whatwg-fetch/fetch.js"><\/script>');
</script>
<script defer src="/assets/js/newnote.js"></script>

<link rel="stylesheet" href="/assets/frontend/alertify.css">
<link rel="stylesheet" href="/assets/frontend/mapbox-gl.css">
@stop
