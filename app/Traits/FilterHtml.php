<?php

declare(strict_types=1);

namespace App\Traits;

use HtmlSanitizer\Sanitizer;

trait FilterHtml
{
    public function filterHtml(string $html): string
    {
        return Sanitizer::create([
            'extensions' => [
                'basic',
                'code',
                'image',
                'list',
                'table',
                'extra',
            ],
        ])->sanitize($html);
    }
}
