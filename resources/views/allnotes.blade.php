@extends('master')

@section('title')
Notes « 
@stop

@section('content')
    <div class="h-feed">
      <!-- the following span stops microformat parses going haywire generating
      a name property for the h-feed -->
      <span class="p-name"></span>
      @foreach ($notes as $note)
      <div class="h-entry">
        @include('templates.note', ['note' => $note])
      </div>
      @endforeach
    </div>
{!! $notes->render() !!}
@stop

@section('scripts')
@include('templates.mapbox-links')

<script src="/assets/frontend/Autolinker.min.js"></script>
<script src="/assets/js/links.js"></script>
<script src="/assets/js/maps.js"></script>

<script src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop

@section('bio')
    @if ($homepage === true)
  <div class="h-card">
    <p>My name is <span class="p-name p-author">Jonny Barnes</span>, and I’m from <a href="https://en.wikipedia.org/wiki/Manchester" class="h-adr p-adr"><span class="p-locality">Manchester</span>, <abbr class="p-country-name" title="United Kingdom">UK</abbr></a>.</p>
    <p>I am active to varying degrees on several <a href="https://indieweb.org/silo">silos</a>:</p>
    <ul class="social-list">
      <li>I keep in touch with friends on <a rel="me" href="https://www.facebook.com/jonnybarnes" class="u-url">Facebook</a></li>
      <li>I follow people I find interesting on <a rel="me" href="https://twitter.com/jonnybarnes" class="u-url">Twitter</a></li>
      <!--<li>I geek out on <a rel="me" href="https://alpha.app.net/jonnybarnes" class="u-url">app.net</a></li>-->
      <li>I exist on <a rel="me" href="https://plus.google.com/117317270900655269082" class="u-url">Google+</a></li>
      <!--<li>I post photos to <a rel="me" href="http://www.flickr.com/photos/22212133@N03/" class="u-url">flickr</a></li>-->
      <li>I push code to <a rel="me" href="https://github.com/jonnybarnes" class="u-url">GitHub</a></li>
      <li>I scrobble songs to <a rel="me" href="https://last.fm/user/jonnymbarnes" class="u-url">last.fm</a> that I listen to on <a rel="me" href="https://open.spotify.com/user/jonnybarnes89" class="u-url">Spotify</a></li>
    </ul>
    <p>My usual online nickname is normally <code class="nickname">jonnybarnes</code> for other services. Here’s a <a href="/assets/img/jmb-bw.png" class="u-photo photo">profile pic</a>. I also have a <a class="pgpkey" href="/assets/jonnybarnes-public-key-ecc.asc">PGP key</a>, with <a href="/notes/5g">fingerprint</a>.</p>
  </div>
    @endif
@stop
