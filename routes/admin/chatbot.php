<?php

use App\Livewire\Admin\Chatbot\Articles;
use App\Livewire\Admin\Chatbot\Conversations;
use App\Livewire\Admin\Chatbot\Settings;
use Illuminate\Support\Facades\Route;

// Chatbot admin routes (settings, knowledge articles, conversations). Owned by the chatbot agent.
// Loaded inside the /admin prefix, "admin." name prefix, and the auth + staff middleware.

Route::prefix('chatbot')->name('chatbot.')->group(function () {
    // Knowledge the bot answers from: every staff member can write and review it.
    Route::livewire('/articles', Articles\Index::class)->name('articles.index');
    Route::livewire('/articles/create', Articles\Form::class)->name('articles.create');
    Route::livewire('/articles/import', Articles\Import::class)->name('articles.import');
    Route::livewire('/articles/{article}/edit', Articles\Form::class)->name('articles.edit');

    // Conversations carry visitors' names and phone numbers: admins only.
    Route::middleware('staff:admin')->group(function () {
        Route::livewire('/conversations', Conversations\Index::class)->name('conversations.index');
        Route::livewire('/conversations/unanswered', Conversations\Unanswered::class)->name('conversations.unanswered');
        Route::livewire('/conversations/{conversation}', Conversations\Show::class)->name('conversations.show');
    });

    // Switching the bot off, its rules and its spending limits: admins only.
    Route::livewire('/settings', Settings::class)->middleware('staff:admin')->name('settings');
});
