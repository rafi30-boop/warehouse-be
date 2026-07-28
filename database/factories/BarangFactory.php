<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BarangFactory extends Factory
{
    protected $model = \App\Models\Barang::class;

    public function definition(): array
    {
        return [
            'sku' => 'BRG-' . $this->faker->unique()->numerify('####'),
            'nama' => $this->faker->word(),
            'kategori_id' => \App\Models\Kategori::factory(),
            'satuan_id' => \App\Models\Satuan::factory(),
            'harga_beli' => $this->faker->numberBetween(1000, 50000),
            'harga_jual' => $this->faker->numberBetween(5000, 100000),
            'status' => 'aktif',
        ];
    }
}