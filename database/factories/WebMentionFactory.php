<?php

namespace Database\Factories;

use App\Models\WebMention;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebMentionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebMention::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'source' => $this->faker->url,
            'target' => url('notes/1'),
            'type' => 'in-reply-to',
            'content' => $this->faker->paragraph,
        ];
    }
}
