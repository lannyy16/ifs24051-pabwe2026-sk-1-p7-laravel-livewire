<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ config('app.name','Stock Barang') }}</title>@vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles</head>
<body><header style="background:#fff;border-bottom:1px solid #e5e9e4;padding:1rem 5%;display:flex;justify-content:space-between">
<a href="/" style="font-weight:700;color:#176b45;text-decoration:none">📦 Stock Barang</a>
<nav style="display:flex;gap:1rem">@auth <a href="/stocks">Stok</a><a href="/profile">Profil</a>@if(auth()->user()->role==='admin')<a href="/admin/users">Admin</a>@endif<form method="POST" action="/logout">@csrf<button>Keluar</button></form>@else<a href="/login">Login</a><a href="/register">Register</a>@endauth</nav></header>
<main style="max-width:1100px;margin:2rem auto;padding:0 1rem">@if(session('success'))<div class="card" style="color:#176b45;margin-bottom:1rem">{{ session('success') }}</div>@endif {{ $slot }}</main>
@livewireScripts</body></html>
