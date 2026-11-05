<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    public function paginate(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user->id, $search, $status, $perPage);
    }

    /** @return Collection<int, Stock> */
    public function all(User $user): Collection
    {
        return $this->repository->allForUser($user->id);
    }

    public function find(int $id): Stock
    {
        return $this->repository->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Stock
    {
        return $this->repository->create($this->normalize($data) + ['user_id' => $user->id]);
    }

    /** @param array<string, mixed> $data */
    public function update(Stock $stock, array $data): Stock
    {
        return $this->repository->update($stock, $this->normalize($data));
    }

    /** Tambah/kurangi quantity (tidak pernah < 0) dan sinkronkan status. */
    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $quantity = max(0, $stock->quantity + $delta);

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock),
        ]);
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCoverFile($stock);
        $this->repository->delete($stock);
    }

    public function setCover(Stock $stock, UploadedFile $file): Stock
    {
        $this->deleteCoverFile($stock);
        $path = $file->store('covers', 'public');

        return $this->repository->update($stock, ['cover_image_path' => $path]);
    }

    public function removeCover(Stock $stock): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => null]);
    }

    /**
     * Simpan banyak item sekaligus (import Excel / hasil AI).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function createMany(User $user, array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $this->create($user, $row);
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $quantity = max(0, (int) ($data['quantity'] ?? 0));
        $minStock = max(0, (int) ($data['min_stock'] ?? 5));
        $price = $data['price'] ?? null;
        $sku = trim((string) ($data['sku'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $unit = trim((string) ($data['unit'] ?? ''));

        return [
            'name' => trim((string) $data['name']),
            'sku' => $sku === '' ? null : $sku,
            'description' => $description === '' ? null : $description,
            'quantity' => $quantity,
            'unit' => $unit === '' ? 'pcs' : $unit,
            'price' => ($price === null || $price === '') ? null : (float) $price,
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock),
        ];
    }

    private function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }
}
