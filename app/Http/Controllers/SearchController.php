<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $notes = Note::search($request->terms)->paginate(10);

        return view('search', compact('notes'));
    }
}
