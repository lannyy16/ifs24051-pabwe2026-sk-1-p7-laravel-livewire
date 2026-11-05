<?php

namespace App\Features\Ai\DTO;

final readonly class GeneratedStockItem
{
    public function __construct(
        public string $name,
        public ?string $sku = null,
        public ?string $description = null,
        public int $quantity = 0,
        public string $unit = 'pcs',
        public ?float $price = null,
        public int $minStock = 5,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $unit = trim((string) ($data['unit'] ?? ''));
        $price = $data['price'] ?? null;

        return new self(
            name: trim((string) ($data['name'] ?? '')),
            sku: filled($data['sku'] ?? null) ? trim((string) $data['sku']) : null,
            description: filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            quantity: max(0, (int) ($data['quantity'] ?? 0)),
            unit: $unit === '' ? 'pcs' : $unit,
            price: is_numeric($price) ? (float) $price : null,
            minStock: max(0, (int) ($data['min_stock'] ?? 5)),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
        ];
    }
}
