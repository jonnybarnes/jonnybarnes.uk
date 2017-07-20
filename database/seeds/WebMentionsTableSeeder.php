<?php

use App\WebMention;
use Illuminate\Database\Seeder;

class WebMentionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $webmention = WebMention::create([
            'source' => 'https://aaornpk.localhost/reply/1',
            'target' => 'https://jonnybarnes.localhost/notes/D',
            'commentable_id' => '13',
            'commentable_type' => 'App\Note',
            'type' => 'in-reply-to',
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"url": ["https://aaronpk.localhost/reply/1"], "name": ["Hi too"], "author": [{"type": ["h-card"], "value": "Aaron Parecki", "properties": {"url": ["https://aaronpk.localhost"], "name": ["Aaron Parecki"], "photo": ["https://aaronparecki.com/images/profile.jpg"]}}], "content": [{"html": "Hi too", "value": "Hi too"}], "published": ["' . date(DATE_W3C) . '"], "in-reply-to": ["https://aaronpk.loclahost/reply/1", "https://jonnybarnes.uk/notes/D"]}}]}'
        ]);
    }
}
