<?php

namespace Database\Factories;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProspekFactory extends Factory
{
    protected $model = Prospek::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->name(),
            'whatsapp'     => '08' . $this->faker->numerify('#########'),
            'type'         => $this->faker->randomElement(['SMA', 'SMK', 'Umum']),
            'status'       => 'PROSPEK',
            'stage_number' => 2,
            'sales_id'     => User::factory()->create(['role' => 'Sales'])->id,
        ];
    }
}
