<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Stok')]
class StockDetail extends Component
{
    use WithFileUploads;

    public Stock $stock;

    public bool $editing = false;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public string $quantity = '0';

    public string $unit = 'pcs';

    public string $price = '';

    public string $min_stock = '5';

    public $cover;

    public function mount(Stock $stock): void
    {
        Gate::authorize('view', $stock);
        $this->stock = $stock;
        $this->fillForm();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:20000',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
        ];
    }

    private function fillForm(): void
    {
        $this->name = $this->stock->name;
        $this->sku = (string) $this->stock->sku;
        $this->description = (string) $this->stock->description;
        $this->quantity = (string) $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price === null ? '' : (string) $this->stock->price;
        $this->min_stock = (string) $this->stock->min_stock;
    }

    public function startEdit(): void
    {
        Gate::authorize('update', $this->stock);
        $this->fillForm();
        $this->resetValidation();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
    }

    public function save(StockService $service): void
    {
        Gate::authorize('update', $this->stock);
        $data = $this->validate();

        $this->stock = $service->update($this->stock, $data);
        $this->editing = false;
        session()->flash('status', 'Stok berhasil diperbarui.');
    }

    public function adjust(int $delta, StockService $service): void
    {
        Gate::authorize('update', $this->stock);
        $this->stock = $service->adjustQuantity($this->stock, $delta);
        $this->quantity = (string) $this->stock->quantity;
    }

    public function saveCover(StockService $service): void
    {
        Gate::authorize('update', $this->stock);
        $this->validate(['cover' => 'required|image|max:2048']);

        $this->stock = $service->setCover($this->stock, $this->cover);
        $this->reset('cover');
        session()->flash('status', 'Cover berhasil diperbarui.');
    }

    public function removeCover(StockService $service): void
    {
        Gate::authorize('update', $this->stock);
        $this->stock = $service->removeCover($this->stock);
    }

    public function delete(StockService $service)
    {
        Gate::authorize('delete', $this->stock);
        $service->delete($this->stock);
        session()->flash('status', 'Stok berhasil dihapus.');

        return $this->redirect(route('stocks.index'));
    }

    public function render()
    {
        return view('livewire.stock.stock-detail');
    }
}
