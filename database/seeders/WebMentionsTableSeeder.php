<?php

namespace Database\Seeders;

use App\Models\WebMention;
use Illuminate\Database\Seeder;

class WebMentionsTableSeeder extends Seeder
{
    /**
     * Seed the webmentions table.
     */
    public function run(): void
    {
        // WebMention Aaron
        WebMention::create([
            'source' => 'https://aaronpk.localhost/reply/1',
            'target' => config('app.url') . '/notes/E',
            'commentable_id' => '14',
            'commentable_type' => 'App\Models\Note',
            'type' => 'in-reply-to',
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"url": ["https://aaronpk.localhost/reply/1"], "name": ["Hi too"], "author": [{"type": ["h-card"], "value": "Aaron Parecki", "properties": {"url": ["https://aaronpk.localhost"], "name": ["Aaron Parecki"], "photo": ["https://aaronparecki.com/images/profile.jpg"]}}], "content": [{"html": "Hi too", "value": "Hi too"}], "published": ["' . date(DATE_W3C) . '"], "in-reply-to": ["https://aaronpk.loclahost/reply/1", "' . config('app.url') .'/notes/E"]}}]}',
        ]);
        // WebMention Tantek
        WebMention::create([
            'source' => 'http://tantek.com/',
            'target' => config('app.url') . '/notes/D',
            'commentable_id' => '13',
            'commentable_type' => 'App\Models\Note',
            'type' => 'in-reply-to',
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"url": ["http://tantek.com/"], "name": ["KUTGW"], "author": [{"type": ["h-card"], "value": "Tantek Celik", "properties": {"url": ["http://tantek.com/"], "name": ["Tantek Celik"]}}], "content": [{"html": "kutgw", "value": "kutgw"}], "published": ["' . date(DATE_W3C) . '"], "in-reply-to": ["' . config('app.url') . '/notes/D"]}}]}',
        ]);
    }
}
