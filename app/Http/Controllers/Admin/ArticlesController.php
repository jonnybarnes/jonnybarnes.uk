<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class ArticlesController extends Controller
{
    public function index(): View
    {
        $posts = Article::select('id', 'title', 'published')->orderBy('id', 'desc')->get();

        return view('admin.articles.index', ['posts' => $posts]);
    }

    public function create(): View
    {
        $message = session('message');

        return view('admin.articles.create', ['message' => $message]);
    }

    public function store(): RedirectResponse
    {
        //if a `.md` is attached use that for the main content.
        if (request()->hasFile('article')) {
            $file = request()->file('article')->openFile();
            $content = $file->fread($file->getSize());
        }
        $main = $content ?? request()->input('main');
        Article::create([
            'url' => request()->input('url'),
            'title' => request()->input('title'),
            'main' => $main,
            'published' => request()->input('published') ?? 0,
        ]);

        return redirect('/admin/blog');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.edit', ['article' => $article]);
    }

    public function update(int $articleId): RedirectResponse
    {
        $article = Article::find($articleId);
        $article->title = request()->input('title');
        $article->url = request()->input('url');
        $article->main = request()->input('main');
        $article->published = request()->input('published') ?? 0;
        $article->save();

        return redirect('/admin/blog');
    }

    public function destroy(int $articleId): RedirectResponse
    {
        Article::where('id', $articleId)->delete();

        return redirect('/admin/blog');
    }
}
