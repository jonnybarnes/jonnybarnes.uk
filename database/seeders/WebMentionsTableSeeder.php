<?php

namespace Database\Seeders;

use App\Models\WebMention;
use Illuminate\Database\Seeder;

class WebMentionsTableSeeder extends Seeder
{
    /**
     * Seed the webmentions table.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function run(): void
    {
        // WebMention reply Aaron
        WebMention::create([
            'source' => 'https://aaronpk.localhost/reply/1',
            'target' => config('app.url') . '/notes/Z',
            'commentable_id' => '5',
            'commentable_type' => 'App\Models\Note',
            'type' => 'in-reply-to',
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"url": ["https://aaronpk.localhost/reply/1"], "name": ["Hi too"], "author": [{"type": ["h-card"], "value": "Aaron Parecki", "properties": {"url": ["https://aaronpk.localhost"], "name": ["Aaron Parecki"], "photo": ["https://aaronparecki.com/images/profile.jpg"]}}], "content": [{"html": "Hi too", "value": "Hi too"}], "published": ["' . date(DATE_W3C) . '"], "in-reply-to": ["https://aaronpk.loclahost/reply/1", "' . config('app.url') .'/notes/E"]}}]}',
        ]);
        // WebMention like Tantek
        WebMention::create([
            'source' => 'https://tantek.com/likes/1',
            'target' => config('app.url') . '/notes/G',
            'commentable_id' => '16',
            'commentable_type' => 'App\Models\Note',
            'type' => 'like-of',
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"url": ["https://tantek.com/likes/1"], "name": ["KUTGW"], "author": [{"type": ["h-card"], "value": "Tantek Celik", "properties": {"url": ["https://tantek.com/"], "name": ["Tantek Celik"], "photo": ["https://tantek.com/photo.jpg"]}}], "content": [{"html": "kutgw", "value": "kutgw"}], "published": ["' . date(DATE_W3C) . '"], "u-like-of": ["' . config('app.url') . '/notes/G"]}}]}',
        ]);
        // WebMention repost Barry
        WebMention::create([
            'source' => 'https://barryfrost.com/reposts/1',
            'target' => config('app.url') . '/notes/C',
            'commentable_id' => '12',
            'commentable_type' => 'App\Models\Note',
            'type' => 'repost-of',
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"url": ["https://barryfrost.com/reposts/1"], "name": ["Kagi is the best"], "author": [{"type": ["h-card"], "value": "Barry Frost", "properties": {"url": ["https://barryfrost.com/"], "name": ["Barry Frost"], "photo": ["https://barryfrost.com/barryfrost.jpg"]}}], "content": [{"html": "Kagi is the Best", "value": "Kagi is the Best"}], "published": ["' . date(DATE_W3C) . '"], "u-repost-of": ["' . config('app.url') . '/notes/C"]}}]}',
        ]);
        // WebMention like from Bluesky
        WebMention::create([
            'source' => 'https://brid.gy/like/bluesky/did:plc:n3jhgiq2ykctnpgzlm6p6b25/at%253A%252F%252Fdid%253Aplc%253An3jhgiq2ykctnpgzlm6p6b25%252Fapp.bsky.feed.post%252F3lalppbcyuc2w/did%253Aplc%253Aia23nh3t37r2lydmmqsixrps',
            'target' => config('app.url') . '/notes/B',
            'commentable_id' => '11',
            'commentable_type' => 'App\Models\Note',
            'type' => 'like-of',
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"uid": ["tag:bsky.app,2013:at://did:plc:n3jhgiq2ykctnpgzlm6p6b25/app.bsky.feed.post/3lalppbcyuc2w_liked_by_did:plc:ia23nh3t37r2lydmmqsixrps"], "url": ["https://bsky.app/profile/jonnybarnes.uk/post/3lalppbcyuc2w#liked_by_did:plc:ia23nh3t37r2lydmmqsixrps"], "name": [""], "author": [{"type": ["h-card"], "value": "bsky.app/profile/little... littledawg13.bsky.social", "properties": {"uid": ["tag:bsky.app,2013:did:plc:ia23nh3t37r2lydmmqsixrps"], "url": ["https://bsky.app/profile/littledawg13.bsky.social", "https://bsky.app/profile/did:plc:ia23nh3t37r2lydmmqsixrps"], "photo": [{"alt": "", "value": "https://cdn.bsky.app/img/avatar/plain/did:plc:ia23nh3t37r2lydmmqsixrps/bafkreifh7ydbyq7qe4maorornocksfnahijxqgx2i5zvvyq6y4i4mydfau@jpeg"}], "nickname": ["littledawg13.bsky.social"]}}], "like-of": ["https://bsky.app/profile/jonnybarnes.uk/post/3lalppbcyuc2w", "http://jonnybarnes.localhost/notes/B"]}}], "rel-urls": []}',
        ]);
    }
}
