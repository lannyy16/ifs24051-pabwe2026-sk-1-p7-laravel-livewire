<div class="mx-auto max-w-3xl">
    <a href="{{ route('stocks.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali ke daftar</a>

    @if (session('status'))
        <div class="my-3 rounded border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="mt-3 space-y-4 rounded-lg bg-white p-6 shadow">
        <div class="flex items-center gap-4">
            @if ($stock->coverUrl())
                <img src="{{ $stock->coverUrl() }}" class="h-32 w-32 rounded object-cover" alt="Cover">
            @else
                <div class="flex h-32 w-32 items-center justify-center rounded bg-slate-100 text-4xl">📦</div>
            @endif
            <form wire:submit="saveCover" class="space-y-2 text-sm">
                <input type="file" wire:model="cover" accept="image/*">
                @error('cover') <p class="text-red-600">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-slate-800 px-3 py-1 text-white">Unggah cover</button>
                    @if ($stock->cover_image_path)
                        <button type="button" wire:click="removeCover" class="rounded bg-red-100 px-3 py-1 text-red-700">Hapus cover</button>
                    @endif
                </div>
            </form>
        </div>

        @if (! $editing)
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $stock->name }}</h1>
                    <p class="text-sm text-slate-500">SKU: {{ $stock->sku ?? '-' }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
            </div>

            <div class="grid grid-cols-3 gap-3 text-sm">
                <div class="rounded bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Quantity</div>
                    <div class="mt-1 flex items-center gap-2 text-lg font-semibold">
                        <button wire:click="adjust(-1)" class="h-7 w-7 rounded bg-slate-200 hover:bg-slate-300">−</button>
                        {{ $stock->quantity }} {{ $stock->unit }}
                        <button wire:click="adjust(1)" class="h-7 w-7 rounded bg-slate-200 hover:bg-slate-300">+</button>
                    </div>
                </div>
                <div class="rounded bg-slate-50 p-3"><div class="text-xs text-slate-500">Harga</div>
                    <div class="text-lg font-semibold">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</div></div>
                <div class="rounded bg-slate-50 p-3"><div class="text-xs text-slate-500">Stok minimum</div>
                    <div class="text-lg font-semibold">{{ $stock->min_stock }}</div></div>
            </div>

            <div>
                <h2 class="mb-1 font-semibold">Deskripsi</h2>
                @if ($stock->description)
                    <div class="max-w-none text-sm leading-relaxed [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:text-lg [&_h2]:font-semibold [&_a]:text-blue-600 [&_a]:underline">
                        {!! \Illuminate\Support\Str::markdown($stock->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                @else
                    <p class="text-sm text-slate-500">Belum ada deskripsi.</p>
                @endif
            </div>

            <div class="flex gap-2">
                <button wire:click="startEdit" id="btn-edit" class="rounded bg-blue-600 px-4 py-2 text-white">Edit</button>
                <button wire:click="delete" wire:confirm="Hapus stok ini?" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
            </div>
        @else
            <form wire:submit="save" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium">Nama</label>
                    <input type="text" wire:model="name" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ([['sku','SKU','text'],['unit','Satuan','text'],['quantity','Quantity','number'],['min_stock','Stok minimum','number'],['price','Harga (Rp)','number']] as [$f,$l,$t])
                        <div>
                            <label class="block text-sm font-medium">{{ $l }}</label>
                            <input type="{{ $t }}" @if($t==='number') min="0" step="any" @endif wire:model="{{ $f }}" class="w-full rounded border border-slate-300 px-3 py-2">
                            @error($f) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="block text-sm font-medium">Deskripsi (Markdown)</label>
                    <div wire:ignore
                         x-data="{ editor: null }"
                         x-init="
                            editor = new toastui.Editor({
                                el: $refs.editorEl,
                                height: '320px',
                                initialEditType: 'markdown',
                                previewStyle: 'tab',
                                initialValue: $wire.description ?? '',
                                events: { change: () => { $wire.description = editor.getMarkdown(); } },
                            });
                         ">
                        <div x-ref="editorEl"></div>
                    </div>
                    @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white">Simpan</button>
                    <button type="button" wire:click="cancelEdit" class="rounded bg-slate-200 px-4 py-2">Batal</button>
                </div>
            </form>
        @endif
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">
@endpush
@push('scripts')
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
@endpush
