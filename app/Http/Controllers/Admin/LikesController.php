<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Like;
use App\Jobs\ProcessLike;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LikesController extends Controller
{
    public function index(): View
    {
        $likes = Like::all();

        return view('admin.likes.index', compact('likes'));
    }

    public function create(): View
    {
        return view('admin.likes.create');
    }

    public function store(): RedirectResponse
    {
        $like = Like::create([
            'url' => normalize_url(request()->input('like_url')),
        ]);
        ProcessLike::dispatch($like);

        return redirect('/admin/likes');
    }

    public function edit(int $likeId): View
    {
        $like = Like::findOrFail($likeId);

        return view('admin.likes.edit', [
            'id' => $like->id,
            'like_url' => $like->url,
        ]);
    }

    public function update(int $likeId): RedirectResponse
    {
        $like = Like::findOrFail($likeId);
        $like->url = normalize_url(request()->input('like_url'));
        $like->save();
        ProcessLike::dispatch($like);

        return redirect('/admin/likes');
    }

    public function destroy(int $likeId): RedirectResponse
    {
        Like::where('id', $likeId)->delete();

        return redirect('/admin/likes');
    }
}
