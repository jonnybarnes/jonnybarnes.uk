@extends('master')

@section('title')
Jonny Barnes
@stop

@section('content')
      <div class="h-card">
        <h2>Hello</h2>
        <p>My name is <span class="p-name p-author">Jonny Barnes</span>, and I’m from <a href="https://en.wikipedia.org/wiki/Manchester" class="h-adr p-adr"><span class="p-locality">Manchester</span>, <abbr class="p-country-name" title="United Kingdom">UK</abbr></a>. I love everything web-related and this is a little place on the web I call my own. My aim now is to try and adhere to the <a href="https://indiewebcamp.com/principles">IndieWeb principles</a> and thus own my data.</p>
        <p>My aim for this homepage is to turn it into a stream of my latest notes and articles I’ve written. Then maybe pull data from places like <a href="https://last.fm">last.fm</a>. Talking of which:</p>

        <h2>Me Around the Web</h2>
        <p>Obviously there’s <a href="/" class="u-url u-uid">this website</a>, which is my main online identity.</p>
        <p>I am active to varying degrees on several <a href="https://indiewebcamp.com/silo">silos</a>:
        <ul class="social-list">
          <li>I keep in touch with friends on <a rel="me" href="https://www.facebook.com/jonnybarnes" class="u-url">Facebook</a></li>
          <li>I follow people I find interesting on <a rel="me" href="https://twitter.com/jonnybarnes" class="u-url">Twitter</a></li>
          <li>I geek out on <a rel="me" href="https://alpha.app.net/jonnybarnes" class="u-url">app.net</a></li>
          <li>I exist on <a rel="me" href="https://plus.google.com/117317270900655269082" class="u-url">Google+</a></li>
          <!--<li>I post photos to <a rel="me" href="http://www.flickr.com/photos/22212133@N03/" class="u-url">flickr</a></li>-->
          <li>I push code to <a rel="me" href="https://github.com/jonnybarnes" class="u-url">GitHub</a></li>
          <li>I scrobble songs to <a rel="me" href="https://last.fm/user/jonnymbarnes" class="u-url">last.fm</a> that I listen to on <a rel="me" href="https://open.spotify.com/user/jonnybarnes89" class="u-url">Spotify</a></li>
        </ul>
        <p>My usual online handle is <span class="nickname">jonnybarnes</span> for other services, though if they’re not listed above then I don’t actively use the service. My usual <a href="/assets/img/jmb-bw.png" class="u-photo photo">profile pic</a>. I also have a <a class="pgpkey" href="/assets/jonnybarnes-public-key-ecc.asc">PGP key</a>, with <a href="/notes/5g">fingerprint</a>.</p>

        <p>Though of course all this activity should eventually “flow” through this website if it is to truely be my online identity.</p>
      </div>
@stop
