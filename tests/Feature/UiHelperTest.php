<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\UiString;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_key_comes_back_in_brackets(): void
    {
        $this->assertSame('[no.such.key]', ui('no.such.key'));
    }

    public function test_reads_the_current_locale_and_replaces_placeholders(): void
    {
        UiString::create(['key' => 't.count', 'ru' => 'Показано: {n} из {all}', 'uz' => '{all} tadan {n} tasi', 'en' => 'Showing {n} of {all}']);

        $this->assertSame('Показано: 3 из 11', ui('t.count', ['n' => 3, 'all' => 11]));

        app()->setLocale('en');
        $this->assertSame('Showing 3 of 11', ui('t.count', ['n' => 3, 'all' => 11]));
        $this->assertSame('11 tadan 3 tasi', ui('t.count', ['n' => 3, 'all' => 11], 'uz'));
    }

    public function test_empty_translation_falls_back_to_russian(): void
    {
        UiString::create(['key' => 't.only_ru', 'ru' => 'Каталог', 'uz' => null, 'en' => '']);

        app()->setLocale('uz');
        $this->assertSame('Каталог', ui('t.only_ru'));
        app()->setLocale('en');
        $this->assertSame('Каталог', ui('t.only_ru'));
    }

    public function test_cache_is_flushed_when_a_string_is_saved_or_deleted(): void
    {
        $string = UiString::create(['key' => 't.live', 'ru' => 'до', 'uz' => 'x', 'en' => 'x']);
        $this->assertSame('до', ui('t.live'));

        $string->update(['ru' => 'после']);
        $this->assertSame('после', ui('t.live'));

        $string->delete();
        $this->assertSame('[t.live]', ui('t.live'));
    }

    public function test_setting_helper_reads_nested_values_and_defaults(): void
    {
        $this->assertSame('fallback', setting('missing', 'fallback'));

        Setting::write('contacts', ['phone' => '+998 (00) 000-00-00', 'is_placeholder' => true]);
        Setting::write('home.sections', [['key' => 'hero', 'visible' => true]]);

        $this->assertSame('+998 (00) 000-00-00', setting('contacts.phone'));
        $this->assertSame('hero', setting('home.sections.0.key'));

        Setting::write('contacts', ['phone' => 'changed']);
        $this->assertSame('changed', setting('contacts.phone'));
    }
}
