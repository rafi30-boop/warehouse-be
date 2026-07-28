<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SatuanFactory extends Factory
{
    protected $model = \App\Models\Satuan::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->word(),
            'singkatan' => strtoupper($this->faker->lexify('??')),
        ];
    }
}