<?php

declare(strict_types=1);

use App\Livewire\Onboarding\Register;
use Illuminate\Support\Facades\Route;

// Central (marketing / onboarding) domain — no clinic auth lives here.
Route::view('/', 'welcome')->name('home');

// Self-serve clinic signup — provisions a tenant + admin.
Route::get('signup', Register::class)->name('signup');
