<?php

use App\Livewire\Pages;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

/*
| Public site. Every route exists three times:
|   Russian  /catalog      name "catalog"
|   Uzbek    /uz/catalog   name "uz.catalog"
|   English  /en/catalog   name "en.catalog"
| Build links with lroute('catalog') and the language switcher with locale_url('uz').
| All public routes are defined here already; page work happens in App\Livewire\Pages\*.
*/

$pages = function () {
    Route::livewire('/', Pages\Home::class)->name('home');
    Route::livewire('/catalog', Pages\Catalog::class)->name('catalog');
    Route::livewire('/compare', Pages\Compare::class)->name('compare');
    Route::livewire('/model/{slug}', Pages\ModelPage::class)->name('model');
    Route::livewire('/dealers', Pages\Dealers::class)->name('dealers');
    Route::livewire('/knowledge', Pages\Knowledge::class)->name('knowledge');

    Route::livewire('/partner/login', Pages\Partner\Login::class)->name('partner.login');
    Route::livewire('/partner', Pages\Partner\Area::class)
        ->middleware(['auth:partner', 'partner.active'])
        ->name('partner');
};

// Prefixed locales first, so "/uz" and "/en" are never read as something else.
foreach (Locales::prefixed() as $locale) {
    Route::prefix($locale)
        ->name($locale.'.')
        ->middleware('locale:'.$locale)
        ->group(fn () => Route::group(['site_page' => true], $pages));
}

Route::middleware('locale:'.Locales::DEFAULT)
    ->group(fn () => Route::group(['site_page' => true], $pages));
