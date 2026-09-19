<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;

class ArticleService
{
    /**
     * @throws \InvalidArgumentException if a published article already has this title
     */
    public function create(array $data): Article
    {
        $attributes = [
            'title' => $data['name'],
            'main' => $data['content'],
            'published' => ($data['post-status'] ?? null) !== 'draft',
        ];

        $existing = Article::where('title', $data['name'])->first();

        if ($existing !== null) {
            if ($existing->published) {
                throw new \InvalidArgumentException("An article titled \"{$data['name']}\" has already been published");
            }

            $existing->update($attributes);

            return $existing;
        }

        return Article::create($attributes);
    }
}
