<div>
    <h1 class="mb-4 text-2xl font-bold">👥 Daftar User</h1>

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / username / email…"
           class="mb-4 w-72 rounded border border-slate-300 px-3 py-2 text-sm">

    <div class="overflow-x-auto rounded bg-white shadow">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr><th class="px-3 py-2">Nama</th><th class="px-3 py-2">Username</th><th class="px-3 py-2">Email</th>
                <th class="px-3 py-2">Role</th><th class="px-3 py-2 text-center">Jumlah stok</th><th class="px-3 py-2"></th></tr>
            </thead>
            <tbody>
            @foreach ($users as $u)
                <tr wire:key="user-{{ $u->id }}" class="border-t">
                    <td class="px-3 py-2 font-medium">{{ $u->name }}</td>
                    <td class="px-3 py-2">{{ $u->username }}</td>
                    <td class="px-3 py-2">{{ $u->email }}</td>
                    <td class="px-3 py-2">{{ $u->role }}</td>
                    <td class="px-3 py-2 text-center">{{ $u->stocks_count }}</td>
                    <td class="px-3 py-2 text-right">
                        <a href="{{ route('admin.users.stocks', $u) }}" class="text-blue-600 hover:underline">Lihat stok</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</div>
