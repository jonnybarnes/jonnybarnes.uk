<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Jonnybarnes\IndieWeb\Numbers;

class ArticlesController extends Controller
{
    /**
     * Show all articles (with pagination).
     *
     * @param  int  $year
     * @param  int  $month
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index(int $year = null, int $month = null)
    {
        $articles = Article::where('published', '1')
                        ->date($year, $month)
                        ->orderBy('updated_at', 'desc')
                        ->simplePaginate(5);

        return view('articles.index', compact('articles'));
    }

    /**
     * Show a single article.
     *
     * @param  int  $year
     * @param  int  $month
     * @param  string  $slug
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function show(int $year, int $month, string $slug)
    {
        $article = Article::where('titleurl', $slug)->firstOrFail();
        if ($article->updated_at->year != $year || $article->updated_at->month != $month) {
            return redirect('/blog/'
                            . $article->updated_at->year
                            . '/' . $article->updated_at->format('m')
                            .'/' . $slug);
        }

        return view('articles.show', compact('article'));
    }

    /**
     * We only have the ID, work out post title, year and month
     * and redirect to it.
     *
     * @param  int  $idFromUrl
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function onlyIdInUrl(int $idFromUrl)
    {
        $realId = resolve(Numbers::class)->b60tonum($idFromUrl);
        $article = Article::findOrFail($realId);

        return redirect($article->link);
    }
}
