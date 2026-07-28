<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StokOpnameFactory extends Factory
{
    protected $model = \App\Models\StokOpname::class;

    public function definition(): array
    {
        return [
            'no_referensi' => 'SO-' . $this->faker->unique()->numerify('####'),
            'gudang_id' => \App\Models\Gudang::factory(),
            'tanggal' => $this->faker->date(),
            'created_by' => \App\Models\User::factory(),
            'status' => 'draft',
        ];
    }
}