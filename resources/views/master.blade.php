<!doctype html>
<html lang="en-GB">
    <head>
        <meta charset="UTF-8">
        <title>@yield('title'){{ config('app.display_name') }}</title>
        <meta name="viewport" content="width=device-width">
        @if (!empty(config('app.font_link')))
            <link rel="stylesheet" href="{{ config('app.font_link') }}">
        @endif
        <link rel="stylesheet" href="/assets/highlight/zenburn.css">
        @production
            <link rel="stylesheet" href="/assets/app.css">
        @endproduction
        <link rel="alternate" type="application/rss+xml" title="Blog RSS Feed" href="/blog/feed.rss">
        <link rel="alternate" type="application/atom+xml" title="Blog Atom Feed" href="/blog/feed.atom">
        <link rel="alternate" type="application/json" title="Blog JSON Feed" href="/blog/feed.json">
        <link rel="alternate" type="application/jf2feed+json" title="Blog JF2 Feed" href="/blog/feed.jf2">
        <link rel="alternate" type="application/rss+xml" title="Notes RSS Feed" href="/notes/feed.rss">
        <link rel="alternate" type="application/atom+xml" title="Notes Atom Feed" href="/notes/feed.atom">
        <link rel="alternate" type="application/json" title="Notes JSON Feed" href="/notes/feed.json">
        <link rel="alternate" type="application/jf2feed+json" title="Notes JF2 Feed" href="/blog/feed.jf2">
        <link rel="openid.server" href="https://indieauth.com/openid">
        <link rel="openid.delegate" href="{{ config('app.url') }}">
        <link rel="authorization_endpoint" href="{{ config('app.authorization_endpoint') }}">
        <link rel="token_endpoint" href="{{ config('app.url') }}/api/token">
        <link rel="micropub" href="{{ config('app.url') }}/api/post">
        <link rel="webmention" href="{{ config('app.url') }}/webmention">
        <link rel="shortcut icon" href="{{ config('app.url') }}/assets/img/memoji-orange-bg-small-fs8.png">
        <link rel="pgpkey" href="/assets/jonnybarnes-public-key-ecc.asc">
    </head>
    <body class="grid">
        <header id="site-header">
            <h1>
                <a rel="author" href="/">{{ config('app.display_name') }}</a>
            </h1>
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

            @isset($bio)
                {!! $bio !!}
            @endisset
        </main>

        <footer>
            <form action="/search" method="get">
                <input type="text" name="q" title="Search"><button type="submit">Search</button>
            </form>
            <p>Built with love: <a href="/colophon">Colophon</a></p>
            <a href="https://indieweb.org"><img src="/assets/img/iwc.svg" alt="Indie Web Camp logo" class="iwc-logo"></a>
        </footer>

        <!--scripts go here when needed-->
        @section('scripts')
            <script src="/assets/app.js"></script>
        @show
    </body>
</html>
