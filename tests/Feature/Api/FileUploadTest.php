<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadTest extends ApiTestCase
{
    public function test_upload_file_success()
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $response = $this->postJson('/api/upload', [
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['url', 'path', 'name']]);

        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    public function test_upload_rejects_invalid_extension()
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $response = $this->postJson('/api/upload', [
            'file' => UploadedFile::fake()->create('virus.exe', 100),
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_requires_file()
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/upload', []);
        $response->assertStatus(422);
    }

    public function test_upload_unauthenticated()
    {
        $response = $this->postJson('/api/upload', []);
        $response->assertStatus(401);
    }
}
