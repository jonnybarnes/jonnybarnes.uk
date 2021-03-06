<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ShortURLsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Short URL Controller
    |--------------------------------------------------------------------------
    |
    |    This redirects the short urls to long ones
    |
    */

    /**
     * Redirect from '/' to the long url.
     *
     * @return RedirectResponse
     */
    public function baseURL(): RedirectResponse
    {
        return redirect(config('app.url'));
    }

    /**
     * Redirect from '/@' to a twitter profile.
     *
     * @return RedirectResponse
     */
    public function twitter(): RedirectResponse
    {
        return redirect('https://twitter.com/jonnybarnes');
    }

    /**
     * Redirect a short url of this site out to a long one based on post type.
     * Further redirects may happen.
     *
     * @param  string  Post type
     * @param  string  Post ID
     * @return RedirectResponse
     */
    public function expandType(string $type, string $postId): RedirectResponse
    {
        if ($type == 't') {
            $type = 'notes';
        }
        if ($type == 'b') {
            $type = 'blog/s';
        }

        return redirect(config('app.url') . '/' . $type . '/' . $postId);
    }
}
