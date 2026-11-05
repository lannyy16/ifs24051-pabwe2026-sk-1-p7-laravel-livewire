<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
@auth
    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 flex-col bg-slate-900 p-4 text-slate-200 md:flex">
            <div class="mb-6 text-lg font-bold text-white">📦 {{ config('app.name') }}</div>
            <nav class="flex flex-1 flex-col gap-1 text-sm">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.users') }}" class="rounded px-3 py-2 hover:bg-slate-700 {{ request()->routeIs('admin.*') ? 'bg-slate-700' : '' }}">👥 Daftar User</a>
                @else
                    <a href="{{ route('stocks.index') }}" class="rounded px-3 py-2 hover:bg-slate-700 {{ request()->routeIs('stocks.*') ? 'bg-slate-700' : '' }}">📋 Stok Barang</a>
                @endif
                <a href="{{ route('profile') }}" class="rounded px-3 py-2 hover:bg-slate-700 {{ request()->routeIs('profile') ? 'bg-slate-700' : '' }}">👤 Profil</a>
            </nav>
            <div class="mb-3 flex items-center gap-2 text-xs text-slate-400">
                @if (auth()->user()->profilePhotoUrl())
                    <img src="{{ auth()->user()->profilePhotoUrl() }}" class="h-8 w-8 rounded-full object-cover" alt="">
                @endif
                <span>{{ auth()->user()->name }}<br>({{ auth()->user()->role }})</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button id="logout-button" class="w-full rounded bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700">Keluar</button>
            </form>
        </aside>
        <main class="flex-1 p-4 md:p-8">
            <div class="mb-4 flex items-center justify-between md:hidden">
                <span class="font-bold">📦 {{ config('app.name') }}</span>
                <div class="flex gap-3 text-sm">
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.users') : route('stocks.index') }}" class="underline">Beranda</a>
                    <a href="{{ route('profile') }}" class="underline">Profil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="underline text-red-600">Keluar</button></form>
                </div>
            </div>
            {{ $slot }}
        </main>
    </div>
@else
    <main class="mx-auto flex min-h-screen max-w-md items-center p-4">
        <div class="w-full">{{ $slot }}</div>
    </main>
@endauth
@stack('scripts')
</body>
</html>
