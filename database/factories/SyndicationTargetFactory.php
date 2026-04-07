<?php

namespace Database\Factories;

use App\Models\SyndicationTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyndicationTarget>
 */
class SyndicationTargetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => $this->faker->url,
            'name' => $this->faker->name,
            'service_name' => $this->faker->name,
            'service_url' => $this->faker->url,
            'service_photo' => $this->faker->url,
            'user_name' => $this->faker->name,
            'user_url' => $this->faker->url,
            'user_photo' => $this->faker->url,
        ];
    }
}
