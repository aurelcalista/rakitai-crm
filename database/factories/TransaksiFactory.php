<?php

namespace Database\Factories;

use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiFactory extends Factory
{
    protected $model = Transaksi::class;

    public function definition(): array
    {
        return [
            'prospek_id'        => Prospek::factory(),
            'user_id'           => User::factory()->create(['role' => 'Sales'])->id,
            'jenis'             => $this->faker->randomElement(['Beli Formulir', 'Pembayaran Termin 1']),
            'nominal'           => $this->faker->numberBetween(500000, 5000000),
            'tanggal'           => now(),
            'notes'             => null,
            'metode_pembayaran' => null,
            'payment_status'    => 'verified',
        ];
    }

    public function pending(): static
    {
        return $this->state(['payment_status' => 'pending', 'metode_pembayaran' => 'gopay']);
    }

    public function termin1(): static
    {
        return $this->state(['jenis' => 'Pembayaran Termin 1']);
    }

    public function formulir(): static
    {
        return $this->state(['jenis' => 'Beli Formulir', 'payment_status' => 'verified']);
    }
}
