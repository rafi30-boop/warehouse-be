<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GudangFactory extends Factory
{
    protected $model = \App\Models\Gudang::class;

    public function definition(): array
    {
        return [
            'kode' => 'GDG-' . $this->faker->unique()->numerify('####'),
            'nama' => $this->faker->company(),
            'alamat' => $this->faker->address(),
            'status' => 'aktif',
        ];
    }
}