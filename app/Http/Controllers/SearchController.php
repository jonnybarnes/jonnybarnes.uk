<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $notes = Note::search($request->terms)->paginate(10);
        foreach ($notes as $note) {
            $note->iso8601_time = $note->updated_at->toISO8601String();
            $note->human_time = $note->updated_at->diffForHumans();
            $photoURLs = [];
            $photos = $note->getMedia();
            foreach ($photos as $photo) {
                $photoURLs[] = $photo->getUrl();
            }
            $note->photoURLs = $photoURLs;
        }

        return view('search', compact('notes'));
    }
}
