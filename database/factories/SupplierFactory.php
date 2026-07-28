<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = \App\Models\Supplier::class;

    public function definition(): array
    {
        return [
            'kode' => 'SPL-' . $this->faker->unique()->numerify('####'),
            'tipe' => $this->faker->randomElement(['perusahaan', 'pribadi']),
            'nama' => $this->faker->company(),
            'email' => $this->faker->email(),
        ];
    }
}