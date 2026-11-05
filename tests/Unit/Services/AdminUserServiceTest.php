<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_users_with_counts_and_search(): void
    {
        $service = app(AdminUserService::class);
        $a = User::factory()->create(['name' => 'Alpha', 'username' => 'alpha', 'email' => 'alpha@x.test']);
        User::factory()->create(['name' => 'Beta', 'username' => 'beta', 'email' => 'beta@x.test']);
        Stock::factory()->count(2)->create(['user_id' => $a->id]);

        $all = $service->paginateUsers();
        $this->assertSame(2, $all->total());
        $this->assertSame(2, $all->firstWhere('id', $a->id)->stocks_count);

        $this->assertSame(1, $service->paginateUsers('beta')->total());
        $this->assertSame(1, $service->paginateUsers('alpha@x')->total());
    }

    public function test_paginate_stocks_of_user(): void
    {
        $service = app(AdminUserService::class);
        $user = User::factory()->create();
        Stock::factory()->withQuantity(0)->create(['user_id' => $user->id, 'name' => 'Habis']);
        Stock::factory()->withQuantity(50)->create(['user_id' => $user->id, 'name' => 'Banyak']);

        $this->assertSame(2, $service->paginateStocksOf($user)->total());
        $this->assertSame(1, $service->paginateStocksOf($user, 'Habis')->total());
        $this->assertSame(1, $service->paginateStocksOf($user, null, 'available')->total());
    }
}
