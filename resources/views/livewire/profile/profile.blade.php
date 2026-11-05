<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-2xl font-bold">👤 Profil</h1>

    @if (session('status'))
        <div class="rounded border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-3 font-semibold">Foto profil</h2>
        <div class="flex items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" class="h-20 w-20 rounded-full object-cover" alt="Foto">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-200 text-2xl">👤</div>
            @endif
            <form wire:submit="savePhoto" class="space-y-2 text-sm">
                <input type="file" wire:model="photo" accept="image/*">
                @error('photo') <p class="text-red-600">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button class="rounded bg-slate-800 px-3 py-1 text-white">Unggah</button>
                    @if ($user->profile_photo_path)
                        <button type="button" wire:click="removePhoto" class="rounded bg-red-100 px-3 py-1 text-red-700">Hapus</button>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-3 font-semibold">Data diri</h2>
        <form wire:submit="saveProfile" class="space-y-3">
            @foreach ([['name','Nama','text'],['username','Username','text'],['email','Email','email']] as [$f,$l,$t])
                <div>
                    <label class="block text-sm font-medium">{{ $l }}</label>
                    <input type="{{ $t }}" wire:model="{{ $f }}" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error($f) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button class="rounded bg-blue-600 px-4 py-2 text-white">Simpan</button>
        </form>
    </section>

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-3 font-semibold">Ubah password</h2>
        <form wire:submit="savePassword" class="space-y-3">
            @foreach ([['current_password','Password saat ini'],['password','Password baru'],['password_confirmation','Konfirmasi password baru']] as [$f,$l])
                <div>
                    <label class="block text-sm font-medium">{{ $l }}</label>
                    <input type="password" wire:model="{{ $f }}" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error($f) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button class="rounded bg-blue-600 px-4 py-2 text-white">Ubah password</button>
        </form>
    </section>
</div>
