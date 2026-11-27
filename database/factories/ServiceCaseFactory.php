<?php

namespace Database\Factories;

use App\Models\ServiceCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCase>
 */
class ServiceCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'family_id' => null,
            'title' => ['ru' => $title, 'uz' => $title, 'en' => $title],
            'note' => null,
            'amount' => 150000,
            'is_from' => false,
            'sort' => 1,
            'is_published' => true,
        ];
    }

    public function withoutAmount(): static
    {
        return $this->state(['amount' => null]);
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
