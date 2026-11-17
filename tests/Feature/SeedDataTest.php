<?php

namespace Tests\Feature;

use App\Models\ChooserOption;
use App\Models\Document;
use App\Models\Family;
use App\Models\ModelSpec;
use App\Models\Partner;
use App\Models\Region;
use App\Models\ScaleModel;
use App\Models\Setting;
use App\Models\UiString;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UiStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        // The schema is migrated once per test run, so seed explicitly (inside the test transaction).
        $this->seed();
    }

    public function test_seed_counts(): void
    {
        $this->assertSame(3, Family::count());
        $this->assertSame(11, ScaleModel::count());
        $this->assertSame(14, Region::count());
        // The prototype's sample dealers and integrators are not imported (owner decision).
        $this->assertSame(0, Partner::count());
        $this->assertSame(26, Document::count());
        $this->assertSame(3, ChooserOption::count());
        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_seeding_twice_changes_nothing(): void
    {
        $before = [ScaleModel::count(), ModelSpec::count(), Partner::count(), UiString::count(), Setting::count(), User::count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before, [ScaleModel::count(), ModelSpec::count(), Partner::count(), UiString::count(), Setting::count(), User::count()]);
    }

    public function test_sample_records_are_flagged_as_placeholders(): void
    {
        $this->assertSame(0, Partner::where('is_placeholder', false)->count());
        $this->assertSame(0, Document::where('is_placeholder', false)->count());
        $this->assertSame(0, Video::where('is_placeholder', false)->count());
        $this->assertTrue(setting('contacts.is_placeholder'));
        $this->assertSame('#FF6400', setting('accent'));
    }

    public function test_spec_value_shapes_are_preserved(): void
    {
        $model = ScaleModel::where('slug', 'rls1515')->with('specs')->firstOrFail();
        $specs = $model->specs->keyBy('key');

        $this->assertSame('parts', $specs['maxCap']->shape());
        $this->assertSame('note', $specs['printSpeed']->shape());
        $this->assertSame('text', $specs['accClass']->shape());
        $this->assertStringStartsWith('90', $specs['printSpeed']->text('en'));
        $this->assertNotEmpty($specs['printSpeed']->note('en'));

        // Rows come back in specGroups order.
        $this->assertSame('maxCap', $model->specs->first()->key);
        $this->assertSame('weighing', $model->specs->first()->group);
    }

    public function test_translations_and_relations(): void
    {
        $family = Family::where('slug', 'label')->firstOrFail();
        $this->assertSame('Label printing scales', $family->tr('name', 'en'));
        $this->assertSame($family->tr('name', 'ru'), $family->tr('name', 'xx'));
        $this->assertSame(4, $family->scaleModels()->count());

        $shop = ChooserOption::where('key', 'shop')->firstOrFail();
        $first = $shop->scaleModels->first();
        $this->assertSame('rls1000a', $first->slug);
        $this->assertIsArray($first->pivot->note);
        $this->assertNotSame('', trv($first->pivot->note, 'uz'));
    }

    public function test_no_price_anywhere_in_the_schema_or_interface_strings(): void
    {
        foreach (Schema::getTables() as $table) {
            foreach (Schema::getColumnListing($table['name']) as $column) {
                $this->assertStringNotContainsStringIgnoringCase('price', $column, "{$table['name']}.{$column}");
            }
        }

        $this->assertSame(0, UiString::where('key', 'like', '%price%')->count());
        foreach (UiString::pluck('key') as $key) {
            $this->assertFalse(UiStringSeeder::isSkipped($key), $key);
        }
        foreach (['cur', 'pa.r.h', 'pa.offer.h', 'pa.calc.h', 'pa.c.total', 'pa.i.no101'] as $key) {
            $this->assertDatabaseMissing('ui_strings', ['key' => $key]);
        }
        $this->assertNull(setting('partnerTerms'));
    }
}
