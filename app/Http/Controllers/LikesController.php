<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\View\View;

class LikesController extends Controller
{
    /**
     * Show the latest likes.
     */
    public function index(): View
    {
        $likes = Like::latest()->paginate(20);

        return view('likes.index', compact('likes'));
    }

    /**
     * Show a single like.
     */
    public function show(Like $like): View
    {
        return view('likes.show', compact('like'));
    }
}
