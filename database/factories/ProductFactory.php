<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory
 */
class ProductFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() {
        return [
            'name' => $this->faker->firstName(),
            'code' => $this->faker->unique()->isbn10(),
            'price' => $this->faker->randomFloat(2, 0, 5000),
            'dimensions' => "{$this->faker->randomNumber(2)}x{$this->faker->randomNumber(2)}",
            'colors' => $this->faker->colorName(),
            'tags' => $this->faker->word(),
            'stock' => $this->faker->numberBetween(0, 500)
        ];
    }
}
