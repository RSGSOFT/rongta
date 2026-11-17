<?php

use App\Http\Middleware\DevAutoLogin;
use Illuminate\Support\Facades\Route;

/*
| Admin panel shell. Loaded from bootstrap/app.php inside the "web" middleware group.
| Do not add routes here. Each admin area owns one file:
|   routes/admin/auth.php     login / logout (no auth middleware)
|   routes/admin/items.php    items: families, models, specs, documents, regions, partners ...
|   routes/admin/content.php  web content: ui strings, FAQ, guide, settings, leads ...
|   routes/admin/chatbot.php  chatbot settings, knowledge, conversations
| Everything is under /admin with route names starting "admin.".
*/

// DevAutoLogin signs a guest in on a local machine only (APP_ENV=local + ADMIN_AUTO_LOGIN=true).
Route::prefix('admin')->name('admin.')->middleware(DevAutoLogin::class)->group(function () {
    require __DIR__.'/admin/auth.php';

    Route::middleware(['auth', 'staff'])->group(function () {
        require __DIR__.'/admin/items.php';
        require __DIR__.'/admin/content.php';
        require __DIR__.'/admin/chatbot.php';
    });
});
