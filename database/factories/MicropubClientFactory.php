<?php

namespace Database\Factories;

use App\Models\MicropubClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class MicropubClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MicropubClient::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'client_url' => $this->faker->url,
            'client_name' => $this->faker->company,
        ];
    }
}
