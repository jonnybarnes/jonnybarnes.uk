<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ArticlesTableSeeder extends Seeder
{
    /**
     * Seed the articles table.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function run(): void
    {
        $now = Carbon::now()->subMonth()->subDays(5);
        $articleFirst = Article::create([
            'title' => 'My New Blog',
            'main' => 'This is *my* new blog. It uses `Markdown`.',
            'published' => 1,
            'created_at' => $now,
        ]);
        DB::table('articles')
            ->where('id', $articleFirst->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subHours(2)->subMinutes(25);
        $articleWithCode = <<<'EOF'
I wrote some code.

I liked writing this:

```php
<?php

declare(strict_types=1);

class Foo
{
    public function __construct()
    {
        echo 'Foo class constructed';
    }
}
```
EOF;
        $articleSecond = Article::create([
            'title' => 'Some code I did',
            'main' => $articleWithCode,
            'published' => 1,
            'created_at' => $now,
        ]);
        DB::table('articles')
            ->where('id', $articleSecond->id)
            ->update(['updated_at' => $now->toDateTimeString()]);
    }
}
