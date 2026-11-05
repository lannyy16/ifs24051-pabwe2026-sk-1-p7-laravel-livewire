<?php

namespace Tests\Unit\Services;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile(): void
    {
        $user = User::factory()->create();

        (new ProfileService)->updateProfile($user, ['name' => 'N', 'username' => 'u1', 'email' => 'n@x.test']);

        $this->assertSame('u1', $user->refresh()->username);
    }

    public function test_update_password(): void
    {
        $user = User::factory()->create(['password' => 'lama']);
        $service = new ProfileService;

        $this->assertFalse($service->updatePassword($user, 'salah', 'baru'));
        $this->assertTrue($service->updatePassword($user, 'lama', 'baru'));
        $this->assertTrue(Hash::check('baru', $user->refresh()->password));
    }

    public function test_photo_lifecycle(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $service = new ProfileService;

        $service->updatePhoto($user, UploadedFile::fake()->image('a.jpg'));
        $first = $user->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $service->updatePhoto($user, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);

        $service->removePhoto($user);
        $this->assertNull($user->refresh()->profile_photo_path);

        $service->removePhoto($user); // tanpa foto: tidak error
        $this->assertNull($user->profile_photo_path);
    }
}
