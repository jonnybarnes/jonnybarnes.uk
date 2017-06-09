<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>{{ config('app.display_name') }}</title>
  <atom:link href="{{ config('app.url') }}/notes/feed.rss" rel="self" type="application/rss+xml" />
  <description>An RSS feed of the notes found on {{ config('url.longurl') }}</description>
  <link>{{ config('app.url') }}/notes</link>
  <lastBuildDate>{{ $buildDate }}</lastBuildDate>
  <ttl>1800</ttl>

  @foreach($notes as $note)
  <item>
    <title>{{ strip_tags($note->note) }}</title>
    <description>
        <![CDATA[
            {!! $note->note !!}
        ]]>
    </description>
    <link>{{ $note->longurl }}</link>
    <guid>{{ $note->longurl}}</guid>
    <pubDate>{{ $note->pubdate }}</pubDate>
  </item>
  @endforeach

</channel>
</rss>
