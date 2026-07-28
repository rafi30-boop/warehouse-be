<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = \App\Models\Customer::class;

    public function definition(): array
    {
        return [
            'kode' => 'CST-' . $this->faker->unique()->numerify('####'),
            'tipe' => $this->faker->randomElement(['perusahaan', 'pribadi']),
            'nama' => $this->faker->company(),
            'email' => $this->faker->email(),
        ];
    }
}