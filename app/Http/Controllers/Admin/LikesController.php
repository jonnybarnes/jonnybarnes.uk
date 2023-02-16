<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLike;
use App\Models\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LikesController extends Controller
{
    /**
     * List the likes that can be edited.
     */
    public function index(): View
    {
        $likes = Like::all();

        return view('admin.likes.index', compact('likes'));
    }

    /**
     * Show the form to make a new like.
     */
    public function create(): View
    {
        return view('admin.likes.create');
    }

    /**
     * Process a request to make a new like.
     */
    public function store(): RedirectResponse
    {
        $like = Like::create([
            'url' => normalize_url(request()->input('like_url')),
        ]);
        ProcessLike::dispatch($like);

        return redirect('/admin/likes');
    }

    /**
     * Display the form to edit a specific like.
     */
    public function edit(int $likeId): View
    {
        $like = Like::findOrFail($likeId);

        return view('admin.likes.edit', [
            'id' => $like->id,
            'like_url' => $like->url,
        ]);
    }

    /**
     * Process a request to edit a like.
     */
    public function update(int $likeId): RedirectResponse
    {
        $like = Like::findOrFail($likeId);
        $like->url = normalize_url(request()->input('like_url'));
        $like->save();
        ProcessLike::dispatch($like);

        return redirect('/admin/likes');
    }

    /**
     * Process the request to delete a like.
     */
    public function destroy(int $likeId): RedirectResponse
    {
        Like::where('id', $likeId)->delete();

        return redirect('/admin/likes');
    }
}
