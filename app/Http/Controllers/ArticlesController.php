<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Jonnybarnes\IndieWeb\Numbers;

class ArticlesController extends Controller
{
    /**
     * Show all articles (with pagination).
     *
     * @param int $year
     * @param int $month
     * @return View
     */
    public function index(int $year = null, int $month = null): View
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
     * @param int $year
     * @param int $month
     * @param string $slug
     * @return RedirectResponse|View
     */
    public function show(int $year, int $month, string $slug)
    {
        $article = Article::where('titleurl', $slug)->firstOrFail();
        if ($article->updated_at->year != $year || $article->updated_at->month != $month) {
            return redirect('/blog/'
                            . $article->updated_at->year
                            . '/' . $article->updated_at->format('m')
                            . '/' . $slug);
        }

        return view('articles.show', compact('article'));
    }

    /**
     * We only have the ID, work out post title, year and month
     * and redirect to it.
     *
     * @param int $idFromUrl
     * @return RedirectResponse
     */
    public function onlyIdInUrl(int $idFromUrl): RedirectResponse
    {
        $realId = resolve(Numbers::class)->b60tonum($idFromUrl);
        $article = Article::findOrFail($realId);

        return redirect($article->link);
    }
}
