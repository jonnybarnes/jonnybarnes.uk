<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Like;
use App\Jobs\ProcessLike;
use App\Http\Controllers\Controller;

class LikesController extends Controller
{
    /**
     * List the likes that can be edited.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $likes = Like::all();

        return view('admin.likes.index', compact('likes'));
    }

    /**
     * Show the form to make a new like.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create()
    {
        return view('admin.likes.create');
    }

    /**
     * Process a request to make a new like.
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $like = Like::create([
            'url' => normalize_url(request()->input('like_url')),
        ]);
        ProcessLike::dispatch($like);

        return redirect('/admin/likes');
    }

    /**
     * Display the form to edit a specific like.
     *
     * @param  int  $likeId
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function edit(int $likeId)
    {
        $like = Like::findOrFail($likeId);

        return view('admin.likes.edit', [
            'id' => $like->id,
            'like_url' => $like->url,
        ]);
    }

    /**
     * Process a request to edit a like.
     *
     * @param  int  $likeId
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function update(int $likeId)
    {
        $like = Like::findOrFail($likeId);
        $like->url = normalize_url(request()->input('like_url'));
        $like->save();
        ProcessLike::dispatch($like);

        return redirect('/admin/likes');
    }

    /**
     * Process the request to delete a like.
     *
     * @param  int  $likeId
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function destroy(int $likeId)
    {
        Like::where('id', $likeId)->delete();

        return redirect('/admin/likes');
    }
}
