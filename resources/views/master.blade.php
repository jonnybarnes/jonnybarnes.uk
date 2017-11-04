<!doctype html>
<html lang="en-GB">
    <head>
        <meta charset="UTF-8">
        <title>@if (App::environment() == 'local'){!! "[testing] -"!!}@endif @yield('title'){{ config('app.display_name') }}</title>
        <meta name="viewport" content="width=device-width">
        <link rel="stylesheet" href="/assets/frontend/normalize.css">
        <link rel="stylesheet" id="colourScheme" href="/assets/css/colours/{{ session('css', 'base16-eighties.css') }}">
        <link rel="stylesheet" href="/assets/css/app.css">
        <link rel="alternate" type="application/rss+xml" title="Blog RSS Feed" href="/blog/feed.rss">
        <link rel="alternate" type="application/atom+xml" title="Blog Atom Feed" href="/blog/feed.atom">
        <link rel="alternate" type="application/json" title="Blog JSON Feed" href="/blog/feed.json">
        <link rel="alternate" type="application/rss+xml" title="Notes RSS Feed" href="/notes/feed.rss">
        <link rel="alternate" type="application/atom+xml" title="Notes Atom Feed" href="/notes/feed.atom">
        <link rel="alternate" type="application/json" title="Notes JSON Feed" href="/notes/feed.json">
        <link rel="openid.server" href="https://indieauth.com/openid">
        <link rel="openid.delegate" href="{{ config('app.url') }}">
        <link rel="authorization_endpoint" href="https://indieauth.com/auth">
        <link rel="token_endpoint" href="{{ config('app.url') }}/api/token">
        <link rel="micropub" href="{{ config('app.url') }}/api/post">
        <link rel="webmention" href="{{ config('app.url') }}/webmention">
        <link rel="shortcut icon" href="/assets/img/jmb-bw.png">
        <link rel="pgpkey" href="/assets/jonnybarnes-public-key-ecc.asc">
    </head>
    <body>
        <header id="topheader">
            <a rel="author" href="/">
                <h1>{{ config('app.display_name') }}</h1>
            </a>
            <nav>
                <a href="/">All</a>
                <a href="/notes">Notes</a>
                <a href="/blog">Articles</a>
                <a href="/bookmarks">Bookmarks</a>
                <a href="/likes">Likes</a>
                <a href="/contacts">Contacts</a>
                <a href="/projects">Projects</a>
            </nav>
        </header>

        <main>
@yield('content')
        </main>
@section('bio')
@show

        @section('scripts')
        <!--scripts go here when needed-->
        <script src="/assets/js/colours.js" async defer></script>
        @show

        <footer>
            <form action="search" method="get">
                <input type="text" name="terms"><button type="submit">Search</button>
            </form>
            @include('templates.colour-scheme')
            <p>The code for <code>{{ config('app.longurl') }}</code> can be found on <a href="https://github.com/jonnybarnes/jonnybarnes.uk">GitHub</a>.</p>
            <p>Built with love: <a href="/colophon">Colophon</a></p>
            <p><a href="https://indieweb.org"><img src="/assets/img/iwc.png" alt="Indie Web Camp logo" class="iwc-logo"></a></p>
        </footer>
        @if (config('app.piwik') === true)
        <!-- Piwik -->
        <script src="https://analytics.jmb.lv/piwik.js" async defer></script>
        <script src="/assets/js/piwik.js"></script>
        @endif
    </body>
</html>
