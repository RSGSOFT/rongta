<?php

namespace Tests\Feature\Chatbot;

use App\Livewire\Chat\Widget;
use App\Support\UzbekText;
use Livewire\Livewire;

/** Uzbek chatbot replies use the site's oʻ/gʻ (U+02BB) and tutuq belgisi (U+02BC). */
class UzbekApostropheTest extends ChatbotTestCase
{
    public function test_wrong_apostrophes_are_replaced(): void
    {
        $this->assertSame('boʻlsa soʻrang tarmogʻi', UzbekText::normalize('bo‘lsa so’rang tarmog\'i'));
        $this->assertSame('Oʻzbekiston Gʻarb', UzbekText::normalize('O`zbekiston G‘arb'));
        $this->assertSame('maʼlumot taʼmirlash', UzbekText::normalize('ma\'lumot ta’mirlash'));
    }

    public function test_correct_text_and_markup_are_left_alone(): void
    {
        $ok = 'Model haqida: [RLS1000/RLS1100B](/uz/model/rls1000b). moʼjiza, oʻ, gʻ, maʼlumot.';
        $this->assertSame($ok, UzbekText::normalize($ok));
        $this->assertSame("'quoted' text", UzbekText::normalize("'quoted' text"));
    }

    public function test_an_uzbek_reply_is_stored_and_streamed_with_the_right_apostrophe(): void
    {
        $frames = [];
        Widget::$recordFrames = function (string $html) use (&$frames) {
            $frames[] = $html;
        };

        try {
            $conversation = $this->passGate('uz');
            $reply = str_repeat('Wi-Fi kerak bo‘lsa, dilerdan so‘rang. ', 8);
            $claude = FakeClaude::bind()->text($reply);
            $claude->chunk = 7;

            // The widget takes its language from the page, as on /uz/...
            app()->setLocale('uz');
            Livewire::test(Widget::class)->set('text', 'Wi-Fi bormi?')->call('send')->call('answer');

            $stored = $conversation->messages()->where('role', 'assistant')->sole()->content;
            $this->assertStringContainsString('boʻlsa', $stored);
            $this->assertStringNotContainsString('‘', $stored);

            $shown = html_entity_decode(strip_tags(implode("\n", $frames)));
            $this->assertStringContainsString('soʻrang', $shown);
            $this->assertStringNotContainsString('‘', $shown);
        } finally {
            Widget::$recordFrames = null;
        }
    }

    public function test_other_languages_are_not_touched(): void
    {
        $conversation = $this->passGate('en');
        FakeClaude::bind()->text("The scale's display shows the weight.");

        app()->setLocale('en');
        Livewire::test(Widget::class)->set('text', 'Display?')->call('send')->call('answer');

        $this->assertSame("The scale's display shows the weight.", $conversation->messages()->where('role', 'assistant')->sole()->content);
    }
}
