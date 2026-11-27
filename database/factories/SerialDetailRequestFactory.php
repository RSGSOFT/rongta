<?php

namespace Database\Factories;

use App\Models\SerialDetailRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pass 'partner_id' and 'serial_number_id': they are not created here.
 *
 * @extends Factory<SerialDetailRequest>
 */
class SerialDetailRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'note' => null,
            'status' => 'new',
        ];
    }
}
