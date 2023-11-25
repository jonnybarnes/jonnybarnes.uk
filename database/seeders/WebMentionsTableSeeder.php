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
    }
}
