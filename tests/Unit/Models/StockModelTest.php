<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_relation_and_cover_url(): void
    {
        $stock = Stock::factory()->create(['quantity' => '7', 'cover_image_path' => 'covers/a.jpg']);

        $this->assertSame(7, $stock->quantity);
        $this->assertInstanceOf(StockStatus::class, $stock->status);
        $this->assertInstanceOf(User::class, $stock->user);
        $this->assertStringEndsWith('covers/a.jpg', $stock->coverUrl());
    }

    public function test_cover_url_null_without_path(): void
    {
        $this->assertNull(Stock::factory()->create()->coverUrl());
    }

    public function test_deleting_user_cascades_to_stocks(): void
    {
        $stock = Stock::factory()->create();
        $stock->user->delete();

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
