<?php

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\Logout;
use Illuminate\Support\Facades\Route;

// Admin login / logout routes (owned by the admin agent). Prefix "/admin", names "admin.*",
// "web" middleware only: no auth here. Guests are redirected to /admin/login, so define
// that path with the name "login" (full name "admin.login").

Route::livewire('/login', Login::class)->name('login');
Route::post('/logout', Logout::class)->name('logout');
