<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_by_role(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect('/admin/users');
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/stocks');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        foreach (['/stocks', '/profile', '/admin/users'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_authenticated_user_cannot_see_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/');
    }

    public function test_member_pages_and_role_protection(): void
    {
        $member = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member)->get('/stocks')->assertOk();
        $this->actingAs($member)->get("/stocks/{$stock->id}")->assertOk();
        $this->actingAs($member)->get('/profile')->assertOk();
        $this->actingAs($member)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_cannot_access_member_stock_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->actingAs($admin)->get("/stocks/{$stock->id}")->assertForbidden();
    }

    public function test_member_cannot_open_other_members_stock(): void
    {
        $stock = Stock::factory()->create();

        $this->actingAs(User::factory()->create())->get("/stocks/{$stock->id}")->assertForbidden();
    }

    public function test_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get("/admin/users/{$member->id}/stocks")->assertOk();
        $this->actingAs(User::factory()->create())->get("/admin/users/{$member->id}/stocks")->assertForbidden();
    }
}
