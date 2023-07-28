<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class SearchController extends Controller
{
    public function search(Request $request): View
    {
        $search = $request->input('q');

        $notes = Note::search($search)
            ->paginate();

        /** @var Note $note */
        foreach ($notes as $note) {
            $note->load('place', 'media', 'client');
        }

        return view('search', compact('search', 'notes'));
    }
}
