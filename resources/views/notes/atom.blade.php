<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Atom feed for {{ config('user.display_name') }}’s notes</title>
    <link rel="self" href="{{ config('app.url') }}/notes/feed.atom" />
    <id>{{ config('app.url')}}/notes</id>
    <updated>{{ $notes[0]->updated_at->toAtomString() }}</updated>

@foreach($notes as $note)
    <entry>
        <title>{{ strip_tags($note->note) }}</title>
        <link href="{{ $note->longurl }}" />
        <id>{{ $note->longurl }}</id>
        <updated>{{ $note->updated_at->toAtomString() }}</updated>
        <content type="html">{{ $note->note }}</content>
        <author>
            <name>{{ config('user.display_name') }}</name>
        </author>
    </entry>
@endforeach
</feed>
