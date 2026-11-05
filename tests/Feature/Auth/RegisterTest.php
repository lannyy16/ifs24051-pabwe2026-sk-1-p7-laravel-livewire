<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_user_registers_as_member_and_is_logged_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi')->set('username', 'budi')->set('email', 'budi@x.test')
            ->set('password', 'rahasia')->set('password_confirmation', 'rahasia')
            ->call('submit')->assertRedirect(route('home'));

        $user = User::where('username', 'budi')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_validation_and_unique_rules(): void
    {
        User::factory()->create(['username' => 'budi', 'email' => 'budi@x.test']);

        Livewire::test(Register::class)
            ->set('name', '')->set('username', 'budi')->set('email', 'budi@x.test')
            ->set('password', 'abc')->set('password_confirmation', 'xyz')
            ->call('submit')
            ->assertHasErrors(['name', 'username', 'email', 'password']);
    }
}
