<?php

use App\Http\Controllers\SeoController;
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
    Route::livewire('/partners', Pages\Partners::class)->name('partners');
    Route::livewire('/knowledge', Pages\Knowledge::class)->name('knowledge');
};

// Files for search engines and AI assistants (one address each, not per language).
Route::controller(SeoController::class)->group(function () {
    Route::get('/sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('/robots.txt', 'robots')->name('robots');
    Route::get('/llms.txt', 'llms')->name('llms');
    Route::get('/llms-full.txt', 'llmsFull')->name('llms-full');
    Route::get('/indexnow.txt', 'indexNowKey')->name('indexnow-key');
});

// Prefixed locales first, so "/uz" and "/en" are never read as something else.
foreach (Locales::prefixed() as $locale) {
    Route::prefix($locale)
        ->name($locale.'.')
        ->middleware('locale:'.$locale)
        ->group(fn () => Route::group(['site_page' => true], $pages));
}

Route::middleware('locale:'.Locales::DEFAULT)
    ->group(fn () => Route::group(['site_page' => true], $pages));
