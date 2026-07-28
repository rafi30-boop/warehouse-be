<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BarangKeluarFactory extends Factory
{
    protected $model = \App\Models\BarangKeluar::class;

    public function definition(): array
    {
        return [
            'no_referensi' => 'BK-' . $this->faker->unique()->numerify('####'),
            'gudang_id' => \App\Models\Gudang::factory(),
            'customer_id' => \App\Models\Customer::factory(),
            'tanggal' => $this->faker->date(),
            'created_by' => \App\Models\User::factory(),
            'status' => 'pending',
        ];
    }
}