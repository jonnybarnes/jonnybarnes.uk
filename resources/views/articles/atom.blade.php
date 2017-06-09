<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Atom feed for {{ config('app.display_name') }}’s blog</title>
    <link rel="self" href="{{ config('app.url') }}/blog/feed.atom" />
    <id>{{ config('app.url')}}/blog</id>
    <updated>{{ $articles[0]->updated_at->toAtomString() }}</updated>

    @foreach($articles as $article)
    <entry>
        <title>{{ $article->title }}</title>
        <link href="{{ config('app.url') }}{{ $article->link }}" />
        <id>{{ config('app.url') }}{{ $article->link }}</id>
        <updated>{{ $article->updated_at->toAtomString() }}</updated>
        <content>{{ $article->main }}</content>
        <author>
            <name>{{ config('app.display_name') }}</name>
        </author>
    </entry>
    @endforeach
</feed>
