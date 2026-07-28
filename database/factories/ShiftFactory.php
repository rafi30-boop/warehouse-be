<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = \App\Models\Shift::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->randomElement(['Shift Pagi', 'Shift Siang', 'Shift Malam']),
            'jam_masuk' => $this->faker->randomElement(['07:00', '14:00', '22:00']),
            'jam_pulang' => $this->faker->randomElement(['15:00', '22:00', '06:00']),
            'status' => 'aktif',
        ];
    }
}