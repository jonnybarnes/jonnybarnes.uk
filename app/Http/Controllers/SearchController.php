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
            $note->load('place', 'media', 'client')
                ->loadCount(['webmentions AS replies' => function ($query) {
                    $query->where('type', 'in-reply-to');
                }])
                ->loadCount(['webmentions AS likes' => function ($query) {
                    $query->where('type', 'like-of');
                }])
                ->loadCount(['webmentions AS reposts' => function ($query) {
                    $query->where('type', 'repost-of');
                }]);
        }

        return view('search', compact('search', 'notes'));
    }
}
