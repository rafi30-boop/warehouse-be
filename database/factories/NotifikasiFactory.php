<?php

namespace Database\Factories;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotifikasiFactory extends Factory
{
    protected $model = Notifikasi::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'judul' => $this->faker->sentence(3),
            'pesan' => $this->faker->sentence(),
            'tipe' => $this->faker->randomElement(['info', 'success', 'warning', 'error']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'is_read' => false,
            'read_at' => null,
        ];
    }
}
