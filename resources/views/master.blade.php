<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <title>@if (App::environment() == 'local'){!! "[testing] -"!!}@endif @yield('title')</title>
  <meta name="viewport" content="width=device-width">
  <link rel="stylesheet" href="/assets/frontend/sanitize.css">
  <link rel="stylesheet" href="/assets/css/global.css">
  <link rel="openid.server" href="https://indieauth.com/openid">
  <link rel="openid.delegate" href="https://jonnybarnes.uk">
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
      <h1>Jonny Barnes</h1>
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
  <script src="https://use.typekit.net/kmb3cdb.js" integrity="sha384-RwBRSXqm+z0+RE6M8Mke3nQ6yWmZv3+DBu36h/9JOE2s2qXm+MaDa6l8E2NIgJvD" crossorigin="anonymous"></script>
  <script>try{Typekit.load({ async: true });}catch(e){}</script>
  @section('scripts')
  <!--scripts go here when needed-->
  @show

</body>
</html>
