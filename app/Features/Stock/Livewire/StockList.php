<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Daftar Stok')]
class StockList extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    // Form create/edit
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public string $quantity = '0';

    public string $unit = 'pcs';

    public string $price = '';

    public string $min_stock = '5';

    public $cover;

    // Import
    public $importFile;

    // AI
    public bool $showAi = false;

    public string $aiPrompt = '';

    public int $aiCount = 5;

    /** @var array<int, array<string, mixed>> */
    public array $aiItems = [];

    /** @var array<int, int|string> */
    public array $aiSelected = [];

    public ?string $aiError = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'cover' => 'nullable|image|max:2048',
        ];
    }

    // ---------- CRUD ----------

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('editingId', 'name', 'sku', 'description', 'price', 'cover');
        $this->quantity = '0';
        $this->unit = 'pcs';
        $this->min_stock = '5';
        $this->showForm = true;
    }

    public function edit(int $id, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('update', $stock);

        $this->resetValidation();
        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = (string) $stock->sku;
        $this->description = (string) $stock->description;
        $this->quantity = (string) $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price === null ? '' : (string) $stock->price;
        $this->min_stock = (string) $stock->min_stock;
        $this->cover = null;
        $this->showForm = true;
    }

    public function save(StockService $service): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $stock = $service->find($this->editingId);
            Gate::authorize('update', $stock);
            $stock = $service->update($stock, $data);
            $message = 'Stok berhasil diperbarui.';
        } else {
            $stock = $service->create(Auth::user(), $data);
            $message = 'Stok berhasil ditambahkan.';
        }

        if ($this->cover) {
            $service->setCover($stock, $this->cover);
        }

        $this->showForm = false;
        $this->reset('cover');
        session()->flash('status', $message);
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function delete(int $id, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('delete', $stock);
        $service->delete($stock);
        session()->flash('status', 'Stok berhasil dihapus.');
    }

    public function adjust(int $id, int $delta, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('update', $stock);
        $service->adjustQuantity($stock, $delta);
    }

    // ---------- Excel ----------

    public function export(StockService $service, StockExcelService $excel)
    {
        $content = $excel->export($service->all(Auth::user()));

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'stok-barang.xlsx');
    }

    public function downloadTemplate(StockExcelService $excel)
    {
        $content = $excel->template();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'template-stok.xlsx');
    }

    public function import(StockService $service, StockExcelService $excel): void
    {
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls|max:5120']);

        try {
            $rows = $excel->import($this->importFile->getRealPath());
        } catch (Throwable) {
            $this->addError('importFile', 'File Excel tidak dapat dibaca.');

            return;
        }

        $count = $service->createMany(Auth::user(), $rows);
        $this->reset('importFile');
        session()->flash('status', "{$count} stok berhasil diimpor.");
    }

    // ---------- AI ----------

    public function openAi(): void
    {
        $this->reset('aiPrompt', 'aiItems', 'aiSelected', 'aiError');
        $this->aiCount = 5;
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->showAi = false;
    }

    public function generateAi(AiClientInterface $ai): void
    {
        $this->validate([
            'aiPrompt' => 'required|string|min:3|max:500',
            'aiCount' => 'required|integer|min:1|max:20',
        ]);

        $this->aiError = null;

        try {
            $items = $ai->generate($this->aiPrompt, $this->aiCount);
        } catch (Throwable $e) {
            $this->aiItems = [];
            $this->aiSelected = [];
            $this->aiError = $e->getMessage();

            return;
        }

        $this->aiItems = array_map(fn ($item) => $item->toArray(), $items);
        $this->aiSelected = array_keys($this->aiItems);
    }

    public function saveAi(StockService $service): void
    {
        $selected = collect($this->aiSelected)->map(fn ($i) => (int) $i)->all();
        $rows = array_values(array_intersect_key($this->aiItems, array_flip($selected)));

        if ($rows === []) {
            $this->aiError = 'Pilih minimal satu item untuk disimpan.';

            return;
        }

        $count = $service->createMany(Auth::user(), $rows);
        $this->showAi = false;
        $this->reset('aiItems', 'aiSelected', 'aiError');
        session()->flash('status', "{$count} stok hasil AI berhasil disimpan.");
    }

    public function render(StockService $service)
    {
        return view('livewire.stock.stock-list', [
            'stocks' => $service->paginate(Auth::user(), $this->search ?: null, $this->status ?: null),
            'statuses' => StockStatus::cases(),
        ]);
    }
}
