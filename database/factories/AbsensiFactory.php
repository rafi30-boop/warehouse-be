<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AbsensiFactory extends Factory
{
    protected $model = \App\Models\Absensi::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'gudang_id' => \App\Models\Gudang::factory(),
            'shift_id' => \App\Models\Shift::factory(),
            'tanggal' => $this->faker->date(),
            'jam_masuk' => '07:00',
            'status' => 'hadir',
        ];
    }
}