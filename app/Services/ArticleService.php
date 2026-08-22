<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;

class ArticleService
{
    public function create(array $data): Article
    {
        return Article::create([
            'title' => $data['name'],
            'main' => $data['content'],
            'published' => ($data['post-status'] ?? null) !== 'draft',
        ]);
    }
}
