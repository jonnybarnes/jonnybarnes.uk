<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Display search results.
     *
     * @return \Illuminate\View\View
     */
    public function search(): View
    {
        $notes = Note::search(request()->input('terms'))->paginate(10);

        return view('search', compact('notes'));
    }
}
