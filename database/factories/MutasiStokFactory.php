<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MutasiStokFactory extends Factory
{
    protected $model = \App\Models\MutasiStok::class;

    public function definition(): array
    {
        return [
            'no_referensi' => 'MS-' . $this->faker->unique()->numerify('####'),
            'barang_id' => \App\Models\Barang::factory(),
            'gudang_asal_id' => \App\Models\Gudang::factory(),
            'gudang_tujuan_id' => \App\Models\Gudang::factory(),
            'qty' => $this->faker->numberBetween(1, 100),
            'tanggal' => $this->faker->date(),
            'created_by' => \App\Models\User::factory(),
            'status' => 'pending',
        ];
    }
}