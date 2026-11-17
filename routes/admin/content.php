<?php

use App\Livewire\Admin\Chooser;
use App\Livewire\Admin\Cities;
use App\Livewire\Admin\Faq;
use App\Livewire\Admin\Guide;
use App\Livewire\Admin\Leads;
use App\Livewire\Admin\PartnerRequests;
use App\Livewire\Admin\Partners;
use App\Livewire\Admin\Regions;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Strings;
use Illuminate\Support\Facades\Route;

// Admin "content" routes: regions, partners, inbox, interface strings, FAQ, buying guide, chooser, settings.
// Already inside: prefix "/admin", name prefix "admin.", middleware web + auth + staff.
// Add ->middleware('staff:admin') for admin-only screens.

// Regions and locations
Route::livewire('/regions', Regions\Index::class)->name('regions.index');
Route::livewire('/regions/create', Regions\Form::class)->name('regions.create');
Route::livewire('/regions/{region}/edit', Regions\Form::class)->name('regions.edit');

Route::livewire('/cities', Cities\Index::class)->name('cities.index');
Route::livewire('/cities/create', Cities\Form::class)->name('cities.create');
Route::livewire('/cities/{city}/edit', Cities\Form::class)->name('cities.edit');

// Partners shown on the Dealers page
Route::livewire('/partners', Partners\Index::class)->name('partners.index');
Route::livewire('/partners/create', Partners\Form::class)->name('partners.create');
Route::livewire('/partners/{partner}/edit', Partners\Form::class)->name('partners.edit');

// Inbox
Route::livewire('/partner-requests', PartnerRequests\Index::class)->name('partner-requests.index');
Route::livewire('/partner-requests/{partnerRequest}', PartnerRequests\Show::class)->name('partner-requests.show');

// Chatbot leads (visitor names and phone numbers): admins only.
Route::middleware('staff:admin')->group(function () {
    Route::livewire('/leads', Leads\Index::class)->name('leads.index');
    Route::livewire('/leads/{lead}', Leads\Show::class)->name('leads.show');
});

// Web content
Route::livewire('/strings', Strings\Index::class)->name('strings.index');
Route::livewire('/strings/{string}/edit', Strings\Form::class)->name('strings.edit');

Route::livewire('/faq', Faq\Index::class)->name('faq.index');
Route::livewire('/faq/topics/create', Faq\TopicForm::class)->name('faq.topics.create');
Route::livewire('/faq/topics/{topic}/edit', Faq\TopicForm::class)->name('faq.topics.edit');
Route::livewire('/faq/items/create', Faq\ItemForm::class)->name('faq.items.create');
Route::livewire('/faq/items/{item}/edit', Faq\ItemForm::class)->name('faq.items.edit');

Route::livewire('/guide', Guide\Index::class)->name('guide.index');
Route::livewire('/guide/entries/create', Guide\EntryForm::class)->name('guide.entries.create');
Route::livewire('/guide/entries/{entry}/edit', Guide\EntryForm::class)->name('guide.entries.edit');
Route::livewire('/guide/checks/create', Guide\CheckForm::class)->name('guide.checks.create');
Route::livewire('/guide/checks/{check}/edit', Guide\CheckForm::class)->name('guide.checks.edit');

Route::livewire('/chooser', Chooser\Index::class)->name('chooser.index');
Route::livewire('/chooser/create', Chooser\Form::class)->name('chooser.create');
Route::livewire('/chooser/{option}/edit', Chooser\Form::class)->name('chooser.edit');

// Site settings: admins only.
Route::middleware('staff:admin')->group(function () {
    Route::livewire('/settings', Settings\Index::class)->name('settings');
    Route::livewire('/settings/trust', Settings\Trust::class)->name('settings.trust');
    Route::livewire('/settings/maker', Settings\Maker::class)->name('settings.maker');
    Route::livewire('/settings/lineart', Settings\DrawingPrompt::class)->name('settings.lineart');
});
