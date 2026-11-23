<?php

namespace Database\Factories;

use App\Models\PartnerPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerPoint>
 */
class PartnerPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);
        $body = fake()->sentence(10);

        return [
            'kind' => 'advantage',
            'title' => ['ru' => $title, 'uz' => $title, 'en' => $title],
            'body' => ['ru' => $body, 'uz' => $body, 'en' => $body],
            'sort' => 1,
            'is_published' => true,
        ];
    }

    public function term(): static
    {
        return $this->state(['kind' => 'term']);
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
