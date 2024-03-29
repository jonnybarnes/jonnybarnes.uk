<?php

namespace Database\Factories;

use App\Models\Note;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @psalm-suppress UnusedClass
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Note::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function definition(): array
    {
        $now = Carbon::now()->subDays(random_int(5, 15));

        return [
            'note' => $this->faker->paragraph,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
