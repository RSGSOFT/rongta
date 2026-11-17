<?php

namespace Tests\Feature\Site;

use Database\Seeders\SiteStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SiteTestCase extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(SiteStringSeeder::class);
    }

    public static function locales(): array
    {
        return ['ru' => ['ru', ''], 'uz' => ['uz', '/uz'], 'en' => ['en', '/en']];
    }

    /** No interface key may be left unresolved: ui() prints a missing key as "[some.key]". */
    protected function assertNoMissingStrings(string $html): void
    {
        // The chat widget has its own strings and seeder (chatbot agent); those keys are not checked here.
        $this->assertDoesNotMatchRegularExpression('/\[(?!bot\.|chat\.)[a-z0-9_]+(\.[A-Za-z0-9_]+)+\]/', $html, 'A ui() key is missing');
    }
}
