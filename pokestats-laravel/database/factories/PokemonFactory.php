<?php

namespace Database\Factories;

use App\Models\Pokemon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pokemon>
 */
class PokemonFactory extends Factory
{
    protected $model = Pokemon::class;

    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->numberBetween(1, 100000),
            'name' => $this->faker->unique()->word(),
            'height' => $this->faker->numberBetween(3, 200),
            'weight' => $this->faker->numberBetween(10, 10000),
            'base_experience' => $this->faker->numberBetween(50, 635),
            'hp' => $this->faker->numberBetween(1, 255),
            'attack' => $this->faker->numberBetween(1, 255),
            'defense' => $this->faker->numberBetween(1, 255),
            'special_attack' => $this->faker->numberBetween(1, 255),
            'special_defense' => $this->faker->numberBetween(1, 255),
            'speed' => $this->faker->numberBetween(1, 255),
            'sprite_url' => $this->faker->imageUrl(),
        ];
    }
}
