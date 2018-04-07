<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Like;

class LikesController extends Controller
{
    /**
     * Show the latest likes.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $likes = Like::latest()->paginate(20);

        return view('likes.index', compact('likes'));
    }

    /**
     * Show a single like.
     *
     * @param  \App\Models\Like  $like
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function show(Like $like)
    {
        return view('likes.show', compact('like'));
    }
}
