<?php

namespace App\Observers;

use HTMLPurifier;
use App\WebMention;
use HTMLPurifier_Config;

class WebMentionObserver
{
    /**
     * Listen for the created event.
     *
     * @param  WebMention  $webmention
     * @return void
     */
    public function created(WebMention $webmention)
    {
        $this->addFilteredHTML($webmention);
    }

    /**
     * Listen for the updated event.
     *
     * @param  WebMention  $webmention
     * @return void
     */
    public function updated(WebMention $webmention)
    {
        $this->addFilteredHTML($webmention);
    }

    /**
     * Filter the HTML in a reply webmention.
     *
     * @param  WebMention  The WebMention model
     * @return void
     */
    private function addFilteredHTML(WebMention $webmention)
    {
        $mf2 = json_decode($webmention->mf2);
        if (isset($mf2['items'][0]['properties']['content'][0]['html'])) {
            $mf2['items'][0]['properties']['content'][0]['html_purified'] = $this->useHTMLPurifier(
                $mf2['items'][0]['properties']['content'][0]['html']
            );
        }
        $webmention->mf2 = json_encode($mf2);
        $webmetion->save();
    }

    /**
     * Set up and use HTMLPurifer on some HTML.
     *
     * @param  string  The HTML to be processed
     * @return string  The processed HTML
     */
    private function useHTMLPurifier($html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path() . '/HTMLPurifier');
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
