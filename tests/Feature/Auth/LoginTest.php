<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_required_element_ids(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_member_can_login_with_username(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')->set('password', 'member')
            ->call('submit')->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_user_can_login_with_email(): void
    {
        User::factory()->create(['email' => 'a@b.test', 'password' => 'secret']);

        Livewire::test(Login::class)
            ->set('login', 'a@b.test')->set('password', 'secret')
            ->call('submit')->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_show_error(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')->set('password', 'salah')
            ->call('submit')->assertHasErrors('login')->assertSet('password', '');

        $this->assertGuest();
    }

    public function test_fields_are_required(): void
    {
        Livewire::test(Login::class)->call('submit')->assertHasErrors(['login', 'password']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
