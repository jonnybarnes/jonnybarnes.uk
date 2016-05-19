<?php

namespace App\Http\Controllers;

use App\Article;
use Illuminate\Http\Request;

class ArticlesAdminController extends Controller
{
    /**
     * Show the new article form.
     *
     * @return \Illuminate\View\Factory view
     */
    public function newArticle()
    {
        $message = session('message');

        return view('admin.newarticle', ['message' => $message]);
    }

    /**
     * List the articles that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function listArticles()
    {
        $posts = Article::select('id', 'title', 'published')->orderBy('id', 'desc')->get();

        return view('admin.listarticles', ['posts' => $posts]);
    }

    /**
     * Show the edit form for an existing article.
     *
     * @param  string  The article id
     * @return \Illuminate\View\Factory view
     */
    public function editArticle($articleId)
    {
        $post = Article::select(
            'title',
            'main',
            'url',
            'published'
        )->where('id', $articleId)->get();

        return view('admin.editarticle', ['id' => $articleId, 'post' => $post]);
    }

    /**
     * Show the delete confirmation form for an article.
     *
     * @param  string  The article id
     * @return \Illuminate\View\Factory view
     */
    public function deleteArticle($articleId)
    {
        return view('admin.deletearticle', ['id' => $articleId]);
    }

    /**
     * Process an incoming request for a new article and save it.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postNewArticle(Request $request)
    {
        $published = $request->input('published');
        if ($published == null) {
            $published = '0';
        }
        //if a `.md` is attached use that for the main content.
        $content = null; //set default value
        if ($request->hasFile('article')) {
            $file = $request->file('article')->openFile();
            $content = $file->fread($file->getSize());
        }
        $main = $content ?? $request->input('main');
        try {
            $article = Article::create(
                [
                    'url' => $request->input('url'),
                    'title' => $request->input('title'),
                    'main' => $main,
                    'published' => $published,
                ]
            );
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $unique = strpos($msg, '1062');
            if ($unique !== false) {
                //We've checked for error 1062, i.e. duplicate titleurl
                return redirect('admin/blog/new')->withInput()->with('message', 'Duplicate title, please change');
            }
            //this isn't the error you're looking for
            throw $e;
        }

        return view('admin.newarticlesuccess', ['id' => $article->id, 'title' => $article->title]);
    }

    /**
     * Process an incoming request to edit an article.
     *
     * @param  string
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate|View\Factory view
     */
    public function postEditArticle($articleId, Request $request)
    {
        $published = $request->input('published');
        if ($published == null) {
            $published = '0';
        }
        $article = Article::find($articleId);
        $article->title = $request->input('title');
        $article->url = $request->input('url');
        $article->main = $request->input('main');
        $article->published = $published;
        $article->save();

        return view('admin.editarticlesuccess', ['id' => $articleId]);
    }

    /**
     * Process a request to delete an aricle.
     *
     * @param  string The article id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteArticle($articleId)
    {
        Article::where('id', $articleId)->delete();

        return view('admin.deletearticlesuccess', ['id' => $articleId]);
    }
}
