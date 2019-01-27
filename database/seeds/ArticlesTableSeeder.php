<?php

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticlesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Article::create([
            'title' => 'My New Blog',
            'main' => 'This is *my* new blog. It uses `Markdown`.',
            'published' => 1,
        ]);

        $articleWithCode = <<<EOF
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
        Article::create([
            'title' => 'Some code I did',
            'main' => $articleWithCode,
            'published' => 1,
        ]);
    }
}
