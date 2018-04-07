<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;

class SearchController extends Controller
{
    /**
     * Display search results.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function search()
    {
        $notes = Note::search(request()->input('terms'))->paginate(10);

        return view('search', compact('notes'));
    }
}
