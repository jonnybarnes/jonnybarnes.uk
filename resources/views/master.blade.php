<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <title>@if (App::environment() == 'local'){!! "[testing] -"!!}@endif @yield('title'){{ env('DISPLAY_NAME') }}</title>
  <meta name="viewport" content="width=device-width">
  <link rel="stylesheet" href="/assets/frontend/normalize.css">
  <link rel="stylesheet" href="/assets/css/app.css">
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
      <h1>{{ env('DISPLAY_NAME') }}</h1>
    </a>
    <nav>
      <a href="/blog">Articles</a>
      <a href="/notes">Notes</a>
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
  @show

<footer>
    <form action="search" method="get">
        <input type="text" name="terms"><button type="submit">Search</button>
    </form>
    <p>The code for <code>{{ env('APP_LONGURL') }}</code> can be found on <a href="https://github.com/jonnybarnes/jonnybarnes.uk">GitHub</a>.</p>
    <p>Built with love: <a href="/colophon">Colophon</a></p>
    <p><a href="https://indieweb.org"><img src="assets/img/iwc.png" alt="Indie Web Camp logo" class="iwc-logo"></a></p>
</footer>
</body>
</html>
