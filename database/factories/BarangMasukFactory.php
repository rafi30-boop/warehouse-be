<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BarangMasukFactory extends Factory
{
    protected $model = \App\Models\BarangMasuk::class;

    public function definition(): array
    {
        return [
            'no_referensi' => 'BM-' . $this->faker->unique()->numerify('####'),
            'gudang_id' => \App\Models\Gudang::factory(),
            'supplier_id' => \App\Models\Supplier::factory(),
            'tanggal' => $this->faker->date(),
            'created_by' => \App\Models\User::factory(),
            'status' => 'pending',
        ];
    }
}