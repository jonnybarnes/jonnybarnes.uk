<?php

namespace Database\Factories;

use App\Models\Like;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LikeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Like::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $now = Carbon::now()->subDays(rand(5, 15));

        return [
            'url' => $this->faker->url,
            'author_name' => $this->faker->name,
            'author_url' => $this->faker->url,
            'content' => '<html><body><div class="h-entry"><div class="e-content">' . $this->faker->realtext() . '</div></div></body></html>',
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ];
    }
}
