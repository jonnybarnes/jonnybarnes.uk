<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @psalm-suppress UnusedClass
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bio>
 */
class BioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraph,
        ];
    }
}
