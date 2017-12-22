<?php

namespace App\Http\Controllers;

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
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function baseURL()
    {
        return redirect(config('app.url'));
    }

    /**
     * Redirect from '/@' to a twitter profile.
     *
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function twitter()
    {
        return redirect('https://twitter.com/jonnybarnes');
    }

    /**
     * Redirect from '/+' to a Google+ profile.
     *
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function googlePlus()
    {
        return redirect('https://plus.google.com/u/0/117317270900655269082/about');
    }

    /**
     * Redirect a short url of this site out to a long one based on post type.
     * Further redirects may happen.
     *
     * @param  string  Post type
     * @param  string  Post ID
     * @return \Illuminate\Routing\Redirector redirect
     */
    public function expandType($type, $postId)
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
