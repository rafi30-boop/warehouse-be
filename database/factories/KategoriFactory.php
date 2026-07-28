<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriFactory extends Factory
{
    protected $model = \App\Models\Kategori::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->word(),
            'deskripsi' => $this->faker->sentence(),
        ];
    }
}