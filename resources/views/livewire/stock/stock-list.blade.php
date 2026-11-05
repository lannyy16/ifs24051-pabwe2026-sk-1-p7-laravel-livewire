<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-bold">📋 Stok Barang</h1>
        <div class="flex flex-wrap gap-2 text-sm">
            <button wire:click="create" id="btn-create" class="rounded bg-blue-600 px-3 py-2 text-white hover:bg-blue-700">+ Tambah</button>
            <button wire:click="openAi" id="btn-ai" class="rounded bg-purple-600 px-3 py-2 text-white hover:bg-purple-700">✨ Generate AI</button>
            <button wire:click="export" class="rounded bg-green-600 px-3 py-2 text-white hover:bg-green-700">⬇ Export Excel</button>
            <button wire:click="downloadTemplate" class="rounded bg-slate-500 px-3 py-2 text-white hover:bg-slate-600">Template</button>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form wire:submit="import" class="mb-4 flex flex-wrap items-center gap-2 rounded bg-white p-3 text-sm shadow">
        <span class="font-medium">Import Excel:</span>
        <input type="file" wire:model="importFile" accept=".xlsx,.xls">
        <button type="submit" class="rounded bg-slate-800 px-3 py-1 text-white">Import</button>
        @error('importFile') <span class="text-red-600">{{ $message }}</span> @enderror
    </form>

    <div class="mb-4 flex flex-wrap gap-2">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / SKU…"
               class="w-64 rounded border border-slate-300 px-3 py-2 text-sm">
        <select wire:model.live="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded bg-white shadow">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="px-3 py-2">Cover</th><th class="px-3 py-2">Nama</th><th class="px-3 py-2">SKU</th>
                <th class="px-3 py-2 text-center">Qty</th><th class="px-3 py-2">Harga</th>
                <th class="px-3 py-2">Status</th><th class="px-3 py-2 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr wire:key="stock-{{ $stock->id }}" class="border-t">
                    <td class="px-3 py-2">
                        @if ($stock->coverUrl())
                            <img src="{{ $stock->coverUrl() }}" class="h-10 w-10 rounded object-cover" alt="">
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded bg-slate-100">📦</div>
                        @endif
                    </td>
                    <td class="px-3 py-2 font-medium"><a href="{{ route('stocks.show', $stock) }}" class="text-blue-600 hover:underline">{{ $stock->name }}</a></td>
                    <td class="px-3 py-2">{{ $stock->sku ?? '-' }}</td>
                    <td class="px-3 py-2">
                        <div class="flex items-center justify-center gap-2">
                            <button wire:click="adjust({{ $stock->id }}, -1)" class="h-6 w-6 rounded bg-slate-200 hover:bg-slate-300">−</button>
                            <span class="w-14 text-center">{{ $stock->quantity }} {{ $stock->unit }}</span>
                            <button wire:click="adjust({{ $stock->id }}, 1)" class="h-6 w-6 rounded bg-slate-200 hover:bg-slate-300">+</button>
                        </div>
                    </td>
                    <td class="px-3 py-2">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                    <td class="px-3 py-2"><span class="rounded-full px-2 py-1 text-xs {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                    <td class="px-3 py-2 text-right">
                        <button wire:click="edit({{ $stock->id }})" class="text-blue-600 hover:underline">Edit</button>
                        <button wire:click="delete({{ $stock->id }})" wire:confirm="Hapus stok ini?" class="ml-2 text-red-600 hover:underline">Hapus</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">Belum ada stok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $stocks->links() }}</div>

    {{-- Modal create/edit --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <form wire:submit="save" class="max-h-full w-full max-w-lg space-y-3 overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold">{{ $editingId ? 'Edit Stok' : 'Tambah Stok' }}</h2>

                <div>
                    <label class="block text-sm font-medium">Nama</label>
                    <input type="text" wire:model="name" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium">SKU</label>
                        <input type="text" wire:model="sku" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('sku') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Satuan</label>
                        <input type="text" wire:model="unit" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('unit') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Quantity</label>
                        <input type="number" min="0" wire:model="quantity" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Stok minimum</label>
                        <input type="number" min="0" wire:model="min_stock" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('min_stock') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium">Harga (Rp)</label>
                    <input type="number" min="0" step="0.01" wire:model="price" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('price') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Deskripsi (Markdown)</label>
                    <textarea wire:model="description" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm"></textarea>
                    @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Cover (opsional)</label>
                    <input type="file" wire:model="cover" accept="image/*">
                    @error('cover') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-slate-500">Status (tersedia / menipis / habis) dihitung otomatis dari quantity dan stok minimum.</p>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded bg-slate-200 px-4 py-2">Batal</button>
                    <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal AI --}}
    @if ($showAi)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="max-h-full w-full max-w-2xl space-y-3 overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold">✨ Generate Stok dengan AI</h2>

                <form wire:submit="generateAi" class="flex flex-wrap items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-sm font-medium">Deskripsikan inventaris</label>
                        <input type="text" wire:model="aiPrompt" placeholder="mis. bahan baku warung kopi"
                               class="w-full rounded border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Jumlah</label>
                        <input type="number" min="1" max="20" wire:model="aiCount" class="w-20 rounded border border-slate-300 px-3 py-2">
                    </div>
                    <button type="submit" class="rounded bg-purple-600 px-4 py-2 text-white" wire:loading.attr="disabled" wire:target="generateAi">
                        <span wire:loading.remove wire:target="generateAi">Generate</span>
                        <span wire:loading wire:target="generateAi">Memproses…</span>
                    </button>
                </form>
                @error('aiPrompt') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('aiCount') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($aiError) <p class="rounded bg-red-50 p-2 text-sm text-red-700">{{ $aiError }}</p> @endif

                @if ($aiItems)
                    <div class="divide-y rounded border">
                        @foreach ($aiItems as $i => $item)
                            <label wire:key="ai-{{ $i }}" class="flex cursor-pointer items-start gap-3 p-3 text-sm">
                                <input type="checkbox" wire:model="aiSelected" value="{{ $i }}" class="mt-1">
                                <span>
                                    <span class="font-medium">{{ $item['name'] }}</span>
                                    <span class="text-slate-500">· {{ $item['sku'] ?? '-' }} · {{ $item['quantity'] }} {{ $item['unit'] }}
                                        @if ($item['price'] !== null) · Rp {{ number_format($item['price'], 0, ',', '.') }} @endif
                                        · min {{ $item['min_stock'] }}</span>
                                    @if ($item['description']) <br><span class="text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($item['description']), 140) }}</span> @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeAi" class="rounded bg-slate-200 px-4 py-2">Tutup</button>
                    @if ($aiItems)
                        <button type="button" wire:click="saveAi" class="rounded bg-blue-600 px-4 py-2 text-white">Simpan terpilih</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
