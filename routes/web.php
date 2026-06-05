<?php

declare(strict_types=1);

use App\Livewire\Onboarding\Register;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Self-serve clinic signup (central domain) — provisions a tenant + admin.
Route::get('signup', Register::class)->name('signup');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
