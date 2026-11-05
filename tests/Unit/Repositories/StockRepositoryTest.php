<?php

namespace Tests\Unit\Repositories;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private StockRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockRepository;
    }

    public function test_paginate_filters_by_user_search_status(): void
    {
        $user = User::factory()->create();
        Stock::factory()->withQuantity(50)->create(['user_id' => $user->id, 'name' => 'Beras', 'sku' => 'AAA']);
        Stock::factory()->withQuantity(0)->create(['user_id' => $user->id, 'name' => 'Gula', 'sku' => 'BBB']);
        Stock::factory()->create();

        $this->assertSame(2, $this->repo->paginateForUser($user->id, null, null)->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, 'Beras', null)->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, 'BBB', null)->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, null, 'out_of_stock')->total());
    }

    public function test_all_for_user_is_sorted_by_name(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'B']);
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'A']);

        $this->assertSame(['A', 'B'], $this->repo->allForUser($user->id)->pluck('name')->all());
    }

    public function test_crud(): void
    {
        $user = User::factory()->create();
        $stock = $this->repo->create(['user_id' => $user->id, 'name' => 'X', 'quantity' => 1, 'unit' => 'pcs', 'min_stock' => 5, 'status' => 'low_stock']);

        $this->assertSame('X', $this->repo->findOrFail($stock->id)->name);
        $this->assertSame('Y', $this->repo->update($stock, ['name' => 'Y'])->name);

        $this->repo->delete($stock);
        $this->expectException(ModelNotFoundException::class);
        $this->repo->findOrFail($stock->id);
    }
}
