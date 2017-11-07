@extends('master')
@section('title')Colophon for @stop

@section('content')
            <h2>Colophon</h2>
            <p>
                This website is written in PHP using the
                <a href="https://laravel.com">Laravel 5.3</a> framework. It is
                designed with various
                <a href="https://indieweb.org">IndieWeb</a> principles in mind.
            </p>
            <h3>IndieWeb</h3>
            <ul>
                <li><a href="https://microformats.org/wiki/microformats2">Microformats2</a></li>
                <ul>
                    <li>h-entry for indiviual notes</li>
                    <li>h-card for people an venues</li>
                    <li>h-feed for the notes feed</li>
                </ul>
                <li><a href="https://indieweb.org/POSSE">
                    <abbr title="Publish on Own Site, Syndicate Elsewhere">POSSE</abbr></a></li>
                <li><a href="https://micropub.net/draft/">Micropub</a>,
                    currently just creating</li>
                <li><a href="https://indieweb.org/webmention">WebMention</a>,
                    both sending and receiving</li>
                <li><a href="https://indieweb.org/backfeed">Backfeed</a> of silo
                    content, this is enabled by the webmention support. The data
                    is provided by the excellent
                    <a href="https://brid.gy">Bridgy</a></li>
                <li><a href="https://indieauth.com">IndieAuth</a> authorisation
                    with <code><a href="https://indieweb.org/rel-me">rel=me</a></code></li>
            </ul>

            <h3>Security</h3>
            <p>The connection to this site is being encrypted using TLSv1.2 and
                ciphers that support
                <a href="https://en.wikipedia.org/wiki/Forward_Secrecy">Forward
                Secrecy</a>. See the
                <a href="https://www.ssllabs.com/ssltest/analyze.html?d=jonnybarnes.uk">
                SSL Labs result</a>. The encryption uses a certifiate issued by
                the <abbr title="Certificate Authority">CA</abbr>
                <a href="https://letsencrypt.org">Let’s Encrypt</a>.
            </p>
@stop
