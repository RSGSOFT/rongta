<?php

namespace Database\Factories;

use App\Models\SerialMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pass 'serial_number_id' (and 'partner_id' for a dealer handover): they are not created here.
 *
 * @extends Factory<SerialMovement>
 */
class SerialMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => SerialMovement::DEALER,
            'happened_on' => '2026-10-10',
            'note' => null,
        ];
    }

    public function service(string $type = 'service_in'): static
    {
        return $this->state(['type' => $type, 'partner_id' => null]);
    }
}
