<?php

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Profile\Livewire\Profile;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Livewire\StockList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:1000-per-second')->group(function () {
    Route::get('/', function () {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        return redirect()->route($user->isAdmin() ? 'admin.users' : 'stocks.index');
    })->name('home');

    Route::middleware('guest')->group(function () {
        Route::get('/login', Login::class)->name('login');
        Route::get('/register', Register::class)->name('register');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', function () {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');

        Route::get('/profile', Profile::class)->name('profile');

        Route::middleware('role:member')->group(function () {
            Route::get('/stocks', StockList::class)->name('stocks.index');
            Route::get('/stocks/{stock}', StockDetail::class)
                ->middleware('owns.stock')
                ->name('stocks.show');
        });

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/users', UserList::class)->name('admin.users');
            Route::get('/users/{user}/stocks', AdminUserStockList::class)->name('admin.users.stocks');
        });
    });
});
