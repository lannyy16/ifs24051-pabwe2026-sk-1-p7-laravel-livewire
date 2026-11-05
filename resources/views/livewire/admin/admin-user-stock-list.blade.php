<div>
    <a href="{{ route('admin.users') }}" class="text-sm text-blue-600 hover:underline">← Kembali ke daftar user</a>
    <h1 class="mb-1 mt-2 text-2xl font-bold">Stok milik {{ $user->name }}</h1>
    <p class="mb-4 text-sm text-slate-500">{{ '@'.$user->username }} · mode hanya-baca</p>

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
            <tr><th class="px-3 py-2">Nama</th><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Qty</th>
                <th class="px-3 py-2">Harga</th><th class="px-3 py-2">Status</th></tr>
            </thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr wire:key="s-{{ $stock->id }}" class="border-t">
                    <td class="px-3 py-2 font-medium">{{ $stock->name }}</td>
                    <td class="px-3 py-2">{{ $stock->sku ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $stock->quantity }} {{ $stock->unit }}</td>
                    <td class="px-3 py-2">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                    <td class="px-3 py-2"><span class="rounded-full px-2 py-1 text-xs {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">User ini belum punya stok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $stocks->links() }}</div>
</div>
