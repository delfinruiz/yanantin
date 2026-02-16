<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Location;
use App\Models\StatusInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'IT-' . $this->faker->unique()->numerify('######'),
            'name' => $this->faker->word() . ' ' . $this->faker->word(),
            'category_id' => Category::inRandomOrder()->first()->id,
            'status_id' => StatusInventory::inRandomOrder()->first()->id,
            'location_id' => Location::inRandomOrder()->first()->id,
        ];
    }
}
