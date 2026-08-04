<?php

namespace Database\Factories;

use App\Models\AktivitasLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AktivitasLogFactory extends Factory
{
    protected $model = AktivitasLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'url' => $this->faker->url(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted', 'login', 'logout']),
            'model' => 'App\\Models\\Barang',
            'model_id' => 1,
            'data_old' => null,
            'data_new' => ['nama' => $this->faker->word()],
        ];
    }
}
