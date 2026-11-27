<?php

namespace Database\Factories;

use App\Models\CabinetAccount;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A registered account waiting for approval. Use approved($partner) for one that can log in.
 *
 * @extends Factory<CabinetAccount>
 */
class CabinetAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company' => fake()->company(),
            'contact_name' => fake()->name(),
            'phone' => '+99890'.fake()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'wants_sales' => true,
            'wants_service' => false,
            'locale' => 'ru',
            'status' => 'pending',
        ];
    }

    public function approved(Partner $partner): static
    {
        return $this->state(['status' => 'approved', 'partner_id' => $partner->id, 'decided_at' => now()]);
    }
}
