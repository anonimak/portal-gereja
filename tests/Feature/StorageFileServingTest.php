<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFileServingTest extends TestCase
{
    public function test_public_disk_url_is_root_relative_by_default(): void
    {
        $url = Storage::disk('public')->url('sample-image.jpg');

        $this->assertEquals('/storage/sample-image.jpg', $url);
    }

    public function test_public_file_can_be_served_successfully(): void
    {
        Storage::fake('public');
        UploadedFile::fake()->image('test-avatar.png', 100, 100)->storeAs('avatars', 'test-avatar.png', 'public');

        $this->assertTrue(Storage::disk('public')->exists('avatars/test-avatar.png'));

        $response = $this->get('/storage/avatars/test-avatar.png');

        $response->assertOk();
    }

    public function test_non_existent_storage_file_returns_404(): void
    {
        Storage::fake('public');

        $response = $this->get('/storage/non-existent-file.png');

        $response->assertNotFound();
    }
}
