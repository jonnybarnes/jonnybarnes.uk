<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <title>@if (App::environment() == 'local'){!! "[testing] -"!!}@endif @yield('title')</title>
  <meta name="viewport" content="width=device-width">
  <link rel="stylesheet" href="{{ elixir('assets/css/sanitize.min.css') }}">
  <link rel="stylesheet" href="{{ elixir('assets/css/global.css') }}">
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
  <script src="//use.typekit.net/kmb3cdb.js"></script>
  <script>try{Typekit.load({ async: true });}catch(e){}</script>
  @section('scripts')
  <!--scripts go here when needed-->
  @show

  {{-- The piwik code that should only be shown in production --}}
  @if (env('PIWIK_URL') !== null)
  <!-- Piwik -->
  <script type="text/javascript">
    var _paq = _paq || [];
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
      var u="{{ env('PIWIK_URL') }}";
      _paq.push(['setTrackerUrl', u+'/piwik.php']);
      _paq.push(['setSiteId', {{ env('PIWIK_SITE_ID') }}]);
      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
      g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'/piwik.js'; s.parentNode.insertBefore(g,s);
    })();
  </script>
  <noscript><p><img src="{{ env('PIWIK_URL') }}/piwik.php?idsite={{ env('PIWIK_SITE_ID') }}" style="border:0;" alt="" /></p></noscript>
  <!-- End Piwik Code -->
  @endif
</body>
</html>
