<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_from_array_normalizes_values(): void
    {
        $dto = GeneratedStockItem::fromArray([
            'name' => '  Kopi ', 'sku' => ' K-1 ', 'description' => ' Enak ',
            'quantity' => '-4', 'unit' => ' ', 'price' => '2500.5', 'min_stock' => '2',
        ]);

        $this->assertSame('Kopi', $dto->name);
        $this->assertSame('K-1', $dto->sku);
        $this->assertSame('Enak', $dto->description);
        $this->assertSame(0, $dto->quantity);
        $this->assertSame('pcs', $dto->unit);
        $this->assertSame(2500.5, $dto->price);
        $this->assertSame(2, $dto->minStock);
    }

    public function test_from_array_defaults(): void
    {
        $dto = GeneratedStockItem::fromArray(['price' => 'abc']);

        $this->assertSame('', $dto->name);
        $this->assertNull($dto->sku);
        $this->assertNull($dto->description);
        $this->assertNull($dto->price);
        $this->assertSame(5, $dto->minStock);
    }

    public function test_to_array(): void
    {
        $dto = new GeneratedStockItem('A', 'S', 'D', 3, 'kg', 10.0, 2);

        $this->assertSame([
            'name' => 'A', 'sku' => 'S', 'description' => 'D', 'quantity' => 3,
            'unit' => 'kg', 'price' => 10.0, 'min_stock' => 2,
        ], $dto->toArray());
    }
}
