<?php

namespace Database\Factories;

use App\Models\SerialBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SerialBatch>
 */
class SerialBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'file_name' => 'import.csv',
            'rows_total' => 1,
            'rows_added' => 1,
            'rows_skipped' => 0,
            'skipped' => null,
        ];
    }
}
