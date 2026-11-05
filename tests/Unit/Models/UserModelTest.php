<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles(): void
    {
        $this->assertTrue(User::factory()->admin()->make()->isAdmin());
        $this->assertFalse(User::factory()->admin()->make()->isMember());
        $this->assertTrue(User::factory()->member()->make()->isMember());
    }

    public function test_stocks_relation_and_password_hashing(): void
    {
        $user = User::factory()->create(['password' => 'rahasia']);
        Stock::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->stocks);
        $this->assertNotSame('rahasia', $user->password);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_profile_photo_url(): void
    {
        $this->assertNull(User::factory()->make()->profilePhotoUrl());
        $this->assertStringEndsWith('p/a.jpg', User::factory()->make(['profile_photo_path' => 'p/a.jpg'])->profilePhotoUrl());
    }
}
