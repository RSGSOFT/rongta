<?php

namespace Database\Factories;

use App\Models\HeroItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroItem>
 */
class HeroItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ['ru' => $name, 'uz' => $name, 'en' => $name],
            'brand' => null,
            'weight_g' => fake()->numberBetween(HeroItem::MIN_WEIGHT_G, HeroItem::MAX_WEIGHT_G),
            'unit_price' => fake()->numberBetween(HeroItem::MIN_UNIT_PRICE, HeroItem::MAX_UNIT_PRICE),
            'sort' => 1,
            'is_published' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
