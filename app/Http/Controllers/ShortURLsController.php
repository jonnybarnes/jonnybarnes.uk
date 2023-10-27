<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * @psalm-suppress UnusedClass
 */
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
     */
    public function baseURL(): RedirectResponse
    {
        return redirect(config('app.url'));
    }

    /**
     * Redirect from '/@' to a twitter profile.
     */
    public function twitter(): RedirectResponse
    {
        return redirect('https://twitter.com/jonnybarnes');
    }

    /**
     * Redirect a short url of this site out to a long one based on post type.
     *
     * Further redirects may happen.
     */
    public function expandType(string $type, string $postId): RedirectResponse
    {
        if ($type === 't') {
            $type = 'notes';
        }
        if ($type === 'b') {
            $type = 'blog/s';
        }

        return redirect(config('app.url') . '/' . $type . '/' . $postId);
    }
}
