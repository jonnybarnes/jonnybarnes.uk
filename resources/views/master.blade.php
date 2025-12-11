<!doctype html>
<html lang="en-GB">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title'){{ config('app.name') }}</title>
        <link rel="stylesheet" href="/assets/highlight/zenburn.css">
        <link rel="stylesheet" href="/assets/css/app.css">
        <link rel="alternate" type="application/rss+xml" title="Blog RSS Feed" href="{{ route('feed.blog.rss') }}">
        <link rel="alternate" type="application/atom+xml" title="Blog Atom Feed" href="{{ route('feed.blog.atom') }}">
        <link rel="alternate" type="application/json" title="Blog JSON Feed" href="{{ route('feed.blog.json') }}">
        <link rel="alternate" type="application/jf2feed+json" title="Blog JF2 Feed" href="{{ route('feed.blog.jf2') }}">
        <link rel="alternate" type="application/rss+xml" title="Notes RSS Feed" href="{{ route('feed.notes.rss') }}">
        <link rel="alternate" type="application/atom+xml" title="Notes Atom Feed" href="{{ route('feed.notes.atom') }}">
        <link rel="alternate" type="application/json" title="Notes JSON Feed" href="{{ route('feed.notes.json') }}">
        <link rel="alternate" type="application/jf2feed+json" title="Notes JF2 Feed" href="{{ route('feed.notes.jf2') }}">
        <link rel="indieauth-metadata" href="{{ route('indieauth.metadata') }}">
        <link rel="authorization_endpoint" href="{{ route('indieauth.start') }}">
        <link rel="token_endpoint" href="{{ route('indieauth.token') }}">
        <link rel="micropub" href="{{ route('micropub-endpoint') }}">
        <link rel="webmention" href="{{ route('webmention-endpoint') }}">
        @if (File::exists(public_path('assets/img/favicon.png')))
            <link rel="icon" href="{{ config('app.url') }}/assets/img/favicon.png">
        @endif
        @if (File::exists(public_path('gpg.key')))
            <link rel="pgpkey" href="{{ config('app.url')}}/gpg.key">
        @endif
    </head>
    <body>
        <header>
            <h1>
                <a rel="author" href="/">{{ config('user.display_name') }}</a>
            </h1>
            <nav>
                <a href="/">All</a>
                <a href="/notes">Notes</a>
                <a href="/blog">Articles</a>
                <a href="/bookmarks">Bookmarks</a>
                <a href="/likes">Likes</a>
                <a href="/contacts">Contacts</a>
                <a href="/projects">Projects</a>
                <a href="{{ route('feed.notes.json') }}" class="rss-icon">@include('icons.rss', ['title' => 'RSS Feed'])</a>
            </nav>
            <div id="theme-selector">
                <button class="toggle" popovertarget="theme-selector-dropdown">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="system" style=""><!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE --><path fill="currentColor" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2S2 6.477 2 12s4.477 10 10 10m0-1.5v-17a8.5 8.5 0 0 1 0 17"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="dark" style="display: none"><!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE --><path fill="currentColor" d="M20.026 17.001c-2.762 4.784-8.879 6.423-13.663 3.661A10 10 0 0 1 3.13 17.68a.75.75 0 0 1 .365-1.132c3.767-1.348 5.785-2.91 6.956-5.146c1.233-2.353 1.551-4.93.689-8.463a.75.75 0 0 1 .769-.927a9.96 9.96 0 0 1 4.457 1.327c4.784 2.762 6.423 8.879 3.66 13.662"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"  class="light" style="display: none"><!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE --><path fill="currentColor" d="M12 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 12 2m5 10a5 5 0 1 1-10 0a5 5 0 0 1 10 0m4.25.75a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5zM12 19a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 12 19m-7.75-6.25a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5zm-.03-8.53a.75.75 0 0 1 1.06 0l1.5 1.5a.75.75 0 0 1-1.06 1.06l-1.5-1.5a.75.75 0 0 1 0-1.06m1.06 15.56a.75.75 0 1 1-1.06-1.06l1.5-1.5a.75.75 0 1 1 1.06 1.06zm14.5-15.56a.75.75 0 0 0-1.06 0l-1.5 1.5a.75.75 0 0 0 1.06 1.06l1.5-1.5a.75.75 0 0 0 0-1.06m-1.06 15.56a.75.75 0 1 0 1.06-1.06l-1.5-1.5a.75.75 0 1 0-1.06 1.06z"/></svg>
                </button>
                <div id="theme-selector-dropdown" popover>
                    <fieldset>
                        <legend>Select theme:</legend>
                        <div>
                            <input type="radio" id="theme-system" name="theme" value="system" checked>
                            <label for="theme-system">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE --><path fill="currentColor" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2S2 6.477 2 12s4.477 10 10 10m0-1.5v-17a8.5 8.5 0 0 1 0 17"/></svg>
                            </label>
                        </div>
                        <div>
                            <input type="radio" id="theme-dark" name="theme" value="dark">
                            <label for="theme-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE --><path fill="currentColor" d="M20.026 17.001c-2.762 4.784-8.879 6.423-13.663 3.661A10 10 0 0 1 3.13 17.68a.75.75 0 0 1 .365-1.132c3.767-1.348 5.785-2.91 6.956-5.146c1.233-2.353 1.551-4.93.689-8.463a.75.75 0 0 1 .769-.927a9.96 9.96 0 0 1 4.457 1.327c4.784 2.762 6.423 8.879 3.66 13.662"/></svg>
                            </label>
                        </div>
                        <div>
                            <input type="radio" id="theme-light" name="theme" value="light">
                            <label for="theme-light">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE --><path fill="currentColor" d="M12 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 12 2m5 10a5 5 0 1 1-10 0a5 5 0 0 1 10 0m4.25.75a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5zM12 19a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 12 19m-7.75-6.25a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5zm-.03-8.53a.75.75 0 0 1 1.06 0l1.5 1.5a.75.75 0 0 1-1.06 1.06l-1.5-1.5a.75.75 0 0 1 0-1.06m1.06 15.56a.75.75 0 1 1-1.06-1.06l1.5-1.5a.75.75 0 1 1 1.06 1.06zm14.5-15.56a.75.75 0 0 0-1.06 0l-1.5 1.5a.75.75 0 0 0 1.06 1.06l1.5-1.5a.75.75 0 0 0 0-1.06m-1.06 15.56a.75.75 0 1 0 1.06-1.06l-1.5-1.5a.75.75 0 1 0-1.06 1.06z"/></svg>
                            </label>
                        </div>
                    </fieldset>
                </div>
            </div>
        </header>

        <main>
            @yield('content')

            @isset($bio)
                <div class="h-card">
                    {!! $bio !!}
                </div>
            @endisset
        </main>

        <footer>
            <div class="footer-actions">
                <search>
                    <form action="/search" method="get">
                        <input type="search" id="search" name="q" aria-label="Search">
                        <button type="submit">Search</button>
                    </form>
                </search>
                @auth()
                    <a href="/logout" class="auth">Logout</a>
                @else
                    <a href="/login" class="auth">Login</a>
                @endauth
            </div>
            <p>Built with love: <a href="/colophon">Colophon</a></p>
            <a href="https://indieweb.org"><img src="/assets/img/iwc.svg" alt="Indie Web Camp logo" class="iwc-logo"></a>
        </footer>

        <!--scripts go here when needed-->
        @section('scripts')
            <script type="module" src="/assets/js/app.js"></script>

            <script type="module" src="/assets/js/is-land.min.js"></script>
            <script type="module" src="/assets/js/snow-fall.js"></script>
            <is-land on:media="(prefers-reduced-motion: no-preference)">
                <snow-fall
                    count="150"
                    style="--snow-fall-color: var(--clr-snow-fall)"
                ></snow-fall>
            </is-land>
        @show
    </body>
</html>
