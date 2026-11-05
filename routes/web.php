<?php
use Illuminate\Support\Facades\Route;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Profile\Livewire\Profile;
use App\Features\Admin\Livewire\UserList;
use App\Features\Admin\Livewire\AdminUserStockList;
Route::get('/',fn()=>auth()->check()?redirect(auth()->user()->role==='admin'?'/admin/users':'/stocks'):redirect('/login'));
Route::middleware('guest')->group(function(){Route::get('/login',Login::class)->name('login');Route::get('/register',Register::class);});
Route::post('/logout',function(){auth()->logout();request()->session()->invalidate();request()->session()->regenerateToken();return redirect('/login');})->middleware('auth');
Route::middleware(['auth','role:member'])->group(function(){Route::get('/stocks',StockList::class);Route::get('/stocks/{stock}',StockDetail::class)->middleware('owns.stock');});
Route::get('/profile',Profile::class)->middleware('auth');
Route::middleware(['auth','role:admin'])->group(function(){Route::get('/admin/users',UserList::class);Route::get('/admin/users/{user}/stocks',AdminUserStockList::class);});
