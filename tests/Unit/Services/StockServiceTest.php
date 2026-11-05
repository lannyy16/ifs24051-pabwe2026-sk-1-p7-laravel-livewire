<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(StockService::class);
        $this->user = User::factory()->create();
    }

    public function test_create_normalizes_input_and_computes_status(): void
    {
        $stock = $this->service->create($this->user, [
            'name' => '  Pulpen ', 'sku' => ' ', 'description' => '', 'quantity' => '4',
            'unit' => '', 'price' => '', 'min_stock' => '5',
        ]);

        $this->assertSame('Pulpen', $stock->name);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertSame('pcs', $stock->unit);
        $this->assertNull($stock->price);
        $this->assertSame('low_stock', $stock->status->value);
        $this->assertSame($this->user->id, $stock->user_id);
    }

    public function test_create_with_price_and_defaults(): void
    {
        $stock = $this->service->create($this->user, ['name' => 'A', 'price' => '1500.5']);

        $this->assertSame('1500.50', $stock->price);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame('out_of_stock', $stock->status->value);
    }

    public function test_update_recomputes_status(): void
    {
        $stock = Stock::factory()->withQuantity(1)->create(['user_id' => $this->user->id]);

        $updated = $this->service->update($stock, ['name' => 'Baru', 'quantity' => 100, 'min_stock' => 5]);

        $this->assertSame('available', $updated->status->value);
    }

    public function test_adjust_quantity_never_below_zero(): void
    {
        $stock = Stock::factory()->withQuantity(2)->create(['user_id' => $this->user->id]);

        $this->assertSame(0, $this->service->adjustQuantity($stock, -5)->quantity);
        $this->assertSame('out_of_stock', $stock->refresh()->status->value);
        $this->assertSame('low_stock', $this->service->adjustQuantity($stock, 3)->status->value);
    }

    public function test_paginate_all_and_find(): void
    {
        Stock::factory()->count(3)->create(['user_id' => $this->user->id]);
        Stock::factory()->create();

        $this->assertSame(3, $this->service->paginate($this->user)->total());
        $this->assertCount(3, $this->service->all($this->user));
        $stock = $this->service->all($this->user)->first();
        $this->assertSame($stock->id, $this->service->find($stock->id)->id);
    }

    public function test_cover_set_replace_remove_and_delete(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->user->id]);

        $this->service->setCover($stock, UploadedFile::fake()->image('a.jpg'));
        $first = $stock->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->service->setCover($stock, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);

        $second = $stock->cover_image_path;
        $this->service->removeCover($stock);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($stock->refresh()->cover_image_path);

        $this->service->setCover($stock, UploadedFile::fake()->image('c.jpg'));
        $third = $stock->cover_image_path;
        $this->service->delete($stock);
        Storage::disk('public')->assertMissing($third);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_delete_without_cover(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->user->id]);

        $this->service->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_create_many(): void
    {
        $count = $this->service->createMany($this->user, [
            ['name' => 'A', 'quantity' => 1],
            ['name' => 'B', 'quantity' => 10],
        ]);

        $this->assertSame(2, $count);
        $this->assertSame(2, Stock::where('user_id', $this->user->id)->count());
        $this->assertSame(0, $this->service->createMany($this->user, []));
    }
}
