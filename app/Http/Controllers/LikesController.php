<?php

namespace App\Http\Controllers;

use App\Like;

class LikesController extends Controller
{
    public function index()
    {
        $likes = Like::latest()->paginate(20);

        return view('likes.index', compact('likes'));
    }

    public function show(Like $like)
    {
        return view('likes.show', compact('like'));
    }
}
