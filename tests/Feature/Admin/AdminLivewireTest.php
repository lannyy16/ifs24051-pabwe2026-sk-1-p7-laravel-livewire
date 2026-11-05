<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_list_shows_stock_counts_and_searches(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Bos']);
        $member = User::factory()->create(['name' => 'Anggota Satu']);
        User::factory()->create(['name' => 'Orang Lain']);
        Stock::factory()->count(3)->create(['user_id' => $member->id]);

        Livewire::actingAs($admin)->test(UserList::class)
            ->assertSee('Anggota Satu')->assertSee('Orang Lain')
            ->set('search', 'Anggota')->assertSee('Anggota Satu')->assertDontSee('Orang Lain');
    }

    public function test_user_stock_list_is_read_only_and_filterable(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        Stock::factory()->withQuantity(0)->create(['user_id' => $member->id, 'name' => 'Barang Habis']);
        Stock::factory()->withQuantity(50)->create(['user_id' => $member->id, 'name' => 'Barang Banyak']);

        $component = Livewire::actingAs($admin)->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Barang Habis')->assertSee('Barang Banyak')
            ->set('search', 'Habis')->assertDontSee('Barang Banyak')
            ->set('search', '')->set('status', 'available')->assertDontSee('Barang Habis')->assertSee('Barang Banyak');

        $this->assertFalse(method_exists($component->instance(), 'delete'));
        $this->assertFalse(method_exists($component->instance(), 'adjust'));
        $this->assertFalse(method_exists($component->instance(), 'save'));
    }

    public function test_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(AdminUserStockList::class, ['user' => User::factory()->create()])
            ->assertSee('belum punya stok');
    }
}
