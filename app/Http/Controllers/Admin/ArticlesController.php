<?php

namespace App\Http\Controllers\Admin;

use App\Article;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ArticlesController extends Controller
{
    /**
     * List the articles that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function index()
    {
        $posts = Article::select('id', 'title', 'published')->orderBy('id', 'desc')->get();

        return view('admin.articles.index', ['posts' => $posts]);
    }

    /**
     * Show the new article form.
     *
     * @return \Illuminate\View\Factory view
     */
    public function create()
    {
        $message = session('message');

        return view('admin.articles.create', ['message' => $message]);
    }

    /**
     * Process an incoming request for a new article and save it.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function store(Request $request)
    {
        //if a `.md` is attached use that for the main content.
        if ($request->hasFile('article')) {
            $file = $request->file('article')->openFile();
            $content = $file->fread($file->getSize());
        }
        $main = $content ?? $request->input('main');
        $article = Article::create(
            [
                'url' => $request->input('url'),
                'title' => $request->input('title'),
                'main' => $main,
                'published' => $request->input('published') ?? 0,
            ]
        );

        return redirect('/admin/blog');
    }

    /**
     * Show the edit form for an existing article.
     *
     * @param  string  The article id
     * @return \Illuminate\View\Factory view
     */
    public function edit($articleId)
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
     * @param  \Illuminate\Http\Request $request
     * @param  string
     * @return \Illuminate|View\Factory view
     */
    public function update(Request $request, $articleId)
    {
        $article = Article::find($articleId);
        $article->title = $request->input('title');
        $article->url = $request->input('url');
        $article->main = $request->input('main');
        $article->published = $request->input('published') ?? 0;
        $article->save();

        return redirect('/admin/blog');
    }

    /**
     * Process a request to delete an aricle.
     *
     * @param  string The article id
     * @return \Illuminate\View\Factory view
     */
    public function destroy($articleId)
    {
        Article::where('id', $articleId)->delete();

        return redirect('/admin/blog');
    }
}
