<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\ClientRepository;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;
    protected $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $clientRepo = app(ClientRepository::class);
        $clientRepo->createPersonalAccessGrantClient(
            'Test Personal Access Client',
            'users'
        );

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('super-admin');
        $this->adminUser = User::find($this->adminUser->id);

        $this->operatorUser = User::factory()->create([
            'name' => 'Operator User',
            'email' => 'operator@test.com',
            'is_active' => true,
        ]);
        $this->operatorUser->assignRole('operator');
        $this->operatorUser = User::find($this->operatorUser->id);
    }

    protected function actingAsAdmin(): void
    {
        Passport::actingAs($this->adminUser, ['*']);
    }

    protected function actingAsOperator(): void
    {
        Passport::actingAs($this->operatorUser, ['*']);
    }
}