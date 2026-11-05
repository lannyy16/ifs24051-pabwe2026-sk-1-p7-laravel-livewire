<?php

namespace Tests\Unit\Enums;

use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\TestCase;

class StockStatusTest extends TestCase
{
    public function test_from_quantity(): void
    {
        $this->assertSame(StockStatus::OutOfStock, StockStatus::fromQuantity(0, 5));
        $this->assertSame(StockStatus::LowStock, StockStatus::fromQuantity(1, 5));
        $this->assertSame(StockStatus::LowStock, StockStatus::fromQuantity(5, 5));
        $this->assertSame(StockStatus::Available, StockStatus::fromQuantity(6, 5));
    }

    public function test_labels_and_badges(): void
    {
        $this->assertSame('Tersedia', StockStatus::Available->label());
        $this->assertSame('Stok Menipis', StockStatus::LowStock->label());
        $this->assertSame('Stok Habis', StockStatus::OutOfStock->label());
        $this->assertStringContainsString('green', StockStatus::Available->badgeClass());
        $this->assertStringContainsString('yellow', StockStatus::LowStock->badgeClass());
        $this->assertStringContainsString('red', StockStatus::OutOfStock->badgeClass());
    }

    public function test_values(): void
    {
        $this->assertSame(['available', 'low_stock', 'out_of_stock'], StockStatus::values());
    }
}
