<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\App;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

trait FilterHtml
{
    public function filterHtml(string $html): string
    {
        return App::make(HtmlSanitizer::class)->sanitize($html);
    }
}
