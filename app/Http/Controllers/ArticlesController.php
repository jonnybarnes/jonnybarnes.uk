<?php

namespace App\Http\Controllers;

use App\Article;
use Jonnybarnes\IndieWeb\Numbers;

class ArticlesController extends Controller
{
    /**
     * Show all articles (with pagination).
     *
     * @return \Illuminate\View\Factory view
     */
    public function showAllArticles($year = null, $month = null)
    {
        $articles = Article::where('published', '1')
                        ->date($year, $month)
                        ->orderBy('updated_at', 'desc')
                        ->simplePaginate(5);

        return view('articles', compact('articles'));
    }

    /**
     * Show a single article.
     *
     * @return \Illuminate\View\Factory view
     */
    public function singleArticle($year, $month, $slug)
    {
        $article = Article::where('titleurl', $slug)->first();
        if ($article->updated_at->year != $year || $article->updated_at->month != $month) {
            throw new \Exception;
        }

        return view('article', compact('article'));
    }

    /**
     * We only have the ID, work out post title, year and month
     * and redirect to it.
     *
     * @return \Illuminte\Routing\RedirectResponse redirect
     */
    public function onlyIdInUrl($inURLId)
    {
        $numbers = new Numbers();
        $realId = $numbers->b60tonum($inURLId);
        $article = Article::findOrFail($realId);

        return redirect($article->link);
    }

    /**
     * Returns the RSS feed.
     *
     * @return \Illuminate\Http\Response
     */
    public function makeRSS()
    {
        $articles = Article::where('published', '1')->orderBy('updated_at', 'desc')->get();
        $buildDate = $articles->first()->updated_at->toRssString();

        return response()
                    ->view('rss', compact('articles', 'buildDate'), 200)
                    ->header('Content-Type', 'application/rss+xml');
    }
}
