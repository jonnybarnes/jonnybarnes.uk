<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;

class ArticleService extends Service
{
    public function create(array $request, string $client = null): Article
    {
        return Article::create([
            'title' => $this->getDataByKey($request, 'name'),
            'main' => $this->getDataByKey($request, 'content'),
            'published' => true,
        ]);
    }
}
