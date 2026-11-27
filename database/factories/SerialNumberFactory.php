<?php

namespace Database\Factories;

use App\Models\SerialBatch;
use App\Models\SerialNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * The scale model is not created here: pass 'scale_model_id' (models need a family and specs).
 *
 * @extends Factory<SerialNumber>
 */
class SerialNumberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'serial_batch_id' => SerialBatch::factory(),
            'serial' => 'RT'.fake()->unique()->numerify('##########'),
            'model_text' => 'RLS1000',
            'imported_on' => '2026-10-01',
        ];
    }
}
