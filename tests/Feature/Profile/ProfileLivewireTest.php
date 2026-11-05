<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create(['password' => 'lama']);
    }

    private function lw()
    {
        return Livewire::actingAs($this->user)->test(Profile::class);
    }

    public function test_mount_fills_form(): void
    {
        $this->lw()->assertSet('username', $this->user->username)->assertSet('email', $this->user->email);
    }

    public function test_update_profile(): void
    {
        $this->lw()->set('name', 'Nama Baru')->set('username', 'baru')->set('email', 'baru@x.test')->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame('baru', $this->user->refresh()->username);
    }

    public function test_update_profile_unique_validation_ignores_self(): void
    {
        $other = User::factory()->create();

        $this->lw()->set('username', $other->username)->call('saveProfile')->assertHasErrors('username');
        $this->lw()->call('saveProfile')->assertHasNoErrors();
    }

    public function test_change_password(): void
    {
        $this->lw()->set('current_password', 'lama')->set('password', 'baru1')->set('password_confirmation', 'baru1')
            ->call('savePassword')->assertHasNoErrors()->assertSet('password', '');

        $this->assertTrue(Hash::check('baru1', $this->user->refresh()->password));
    }

    public function test_change_password_wrong_current(): void
    {
        $this->lw()->set('current_password', 'salah')->set('password', 'baru1')->set('password_confirmation', 'baru1')
            ->call('savePassword')->assertHasErrors('current_password');
    }

    public function test_photo_upload_replace_and_remove(): void
    {
        $component = $this->lw()->set('photo', UploadedFile::fake()->image('a.jpg'))->call('savePhoto');
        $first = $this->user->refresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);
        $this->assertNotNull($this->user->profilePhotoUrl());

        $component->set('photo', UploadedFile::fake()->image('b.jpg'))->call('savePhoto');
        Storage::disk('public')->assertMissing($first);

        $component->call('removePhoto');
        $this->assertNull($this->user->refresh()->profile_photo_path);
    }

    public function test_photo_validation(): void
    {
        $this->lw()->call('savePhoto')->assertHasErrors('photo');
    }
}
