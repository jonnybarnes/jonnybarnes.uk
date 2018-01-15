<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ArticlesController extends Controller
{
    /**
     * List the articles that can be edited.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $posts = Article::select('id', 'title', 'published')->orderBy('id', 'desc')->get();

        return view('admin.articles.index', ['posts' => $posts]);
    }

    /**
     * Show the new article form.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $message = session('message');

        return view('admin.articles.create', ['message' => $message]);
    }

    /**
     * Process an incoming request for a new article and save it.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(): RedirectResponse
    {
        //if a `.md` is attached use that for the main content.
        if (request()->hasFile('article')) {
            $file = request()->file('article')->openFile();
            $content = $file->fread($file->getSize());
        }
        $main = $content ?? request()->input('main');
        $article = Article::create(
            [
                'url' => request()->input('url'),
                'title' => request()->input('title'),
                'main' => $main,
                'published' => request()->input('published') ?? 0,
            ]
        );

        return redirect('/admin/blog');
    }

    /**
     * Show the edit form for an existing article.
     *
     * @param  int  $articleId
     * @return \Illuminate\View\View
     */
    public function edit(int $articleId): View
    {
        $post = Article::select(
            'title',
            'main',
            'url',
            'published'
        )->where('id', $articleId)->get();

        return view('admin.articles.edit', ['id' => $articleId, 'post' => $post]);
    }

    /**
     * Process an incoming request to edit an article.
     *
     * @param  int  $articleId
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Process a request to delete an aricle.
     *
     * @param  int  $articleId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $articleId): RedirectResponse
    {
        Article::where('id', $articleId)->delete();

        return redirect('/admin/blog');
    }
}
