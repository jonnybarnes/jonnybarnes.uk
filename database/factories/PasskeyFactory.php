<?php

namespace Database\Factories;

use App\Models\Passkey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Passkey>
 */
class PasskeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Passkey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 1000),
            'passkey_id' => $this->faker->uuid,
            'passkey' => $this->faker->sha256,
            'transports' => ['internal'],
        ];
    }
}
