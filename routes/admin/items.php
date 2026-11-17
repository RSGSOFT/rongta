<?php

use App\Livewire\Admin\Activity;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Items\Documents;
use App\Livewire\Admin\Items\Families;
use App\Livewire\Admin\Items\Models;
use App\Livewire\Admin\Items\Videos;
use App\Livewire\Admin\Staff;
use Illuminate\Support\Facades\Route;

// Admin "items" routes: catalog, documents, regions and locations, partners.
// Already inside: prefix "/admin", name prefix "admin.", middleware web + auth + staff.
// Add ->middleware('staff:admin') for admin-only screens.

Route::livewire('/', Dashboard\Index::class)->name('dashboard');

// Items: any active staff member (admin or editor).
Route::livewire('/families', Families\Index::class)->name('families.index');
Route::livewire('/families/create', Families\Form::class)->name('families.create');
Route::livewire('/families/{family}/edit', Families\Form::class)->name('families.edit');

Route::livewire('/models', Models\Index::class)->name('models.index');
Route::livewire('/models/create', Models\Form::class)->name('models.create');
Route::livewire('/models/{model}/edit', Models\Form::class)->name('models.edit');
Route::livewire('/models/{model}/specs', Models\Specs::class)->name('models.specs');

Route::livewire('/documents', Documents\Index::class)->name('documents.index');
Route::livewire('/documents/create', Documents\Form::class)->name('documents.create');
Route::livewire('/documents/{document}/edit', Documents\Form::class)->name('documents.edit');

Route::livewire('/videos', Videos\Index::class)->name('videos.index');
Route::livewire('/videos/create', Videos\Form::class)->name('videos.create');
Route::livewire('/videos/{video}/edit', Videos\Form::class)->name('videos.edit');

// System: admins only.
Route::middleware('staff:admin')->group(function () {
    Route::livewire('/staff', Staff\Index::class)->name('staff.index');
    Route::livewire('/staff/create', Staff\Form::class)->name('staff.create');
    Route::livewire('/staff/{user}/edit', Staff\Form::class)->name('staff.edit');

    Route::livewire('/activity', Activity\Index::class)->name('activity');
});
