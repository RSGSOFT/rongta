<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\HeroItems;
use App\Models\ActivityLog;
use App\Models\HeroItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class HeroItemsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        HeroItem::query()->delete();
        $this->actingAs($this->editor());
    }

    public function test_editor_opens_the_screens(): void
    {
        $item = HeroItem::factory()->create();

        foreach (['/admin/hero-items', '/admin/hero-items/create', "/admin/hero-items/{$item->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_item_is_created_with_weight_in_kilograms_and_logged(): void
    {
        Livewire::test(HeroItems\Form::class)
            ->set('form.name', $this->tr('Яблоки красные'))
            ->set('form.brand', '  Bogʻ  ')
            ->set('form.weight_kg', '1,25')
            ->set('form.unit_price', 18000)
            ->assertSee("22\u{00A0}500")
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.hero-items.index'));

        $item = HeroItem::sole();
        $this->assertSame('Яблоки красные uz', $item->tr('name', 'uz'));
        $this->assertSame('Bogʻ', $item->brand);
        $this->assertSame(1250, $item->weight_g);
        $this->assertSame(18000, $item->unit_price);
        $this->assertSame(22500, $item->total());
        $this->assertTrue($item->is_published);
        $this->assertSame(1, ActivityLog::where('action', 'created')->where('subject_type', HeroItem::class)->count());
    }

    public static function outOfRange(): array
    {
        return [
            'lighter than 40 g' => ['0.039', 18000, 'form.weight_kg'],
            'heavier than 30 kg' => ['30.001', 18000, 'form.weight_kg'],
            'four decimals' => ['1.2505', 18000, 'form.weight_kg'],
            'not a number' => ['abc', 18000, 'form.weight_kg'],
            'below 10' => ['1.250', 9, 'form.unit_price'],
            'above five million' => ['1.250', 5000001, 'form.unit_price'],
            'fractional' => ['1.250', '12000.5', 'form.unit_price'],
        ];
    }

    #[DataProvider('outOfRange')]
    public function test_weight_and_unit_number_must_stay_in_range(string $weight, int|string $unitPrice, string $field): void
    {
        Livewire::test(HeroItems\Form::class)
            ->set('form.name', $this->tr('Яблоки'))
            ->set('form.weight_kg', $weight)
            ->set('form.unit_price', $unitPrice)
            ->call('save')
            ->assertHasErrors([$field]);

        $this->assertSame(0, HeroItem::count());
    }

    public function test_range_limits_and_a_missing_name(): void
    {
        Livewire::test(HeroItems\Form::class)
            ->set('form.weight_kg', '30')
            ->set('form.unit_price', 5000000)
            ->call('save')
            ->assertHasErrors(['form.name.ru' => 'required'])
            ->assertHasNoErrors(['form.weight_kg', 'form.unit_price']);

        Livewire::test(HeroItems\Form::class)
            ->set('form.name', $this->tr('Шоколад'))
            ->set('form.weight_kg', '0.04')
            ->set('form.unit_price', 10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(40, HeroItem::sole()->weight_g);
    }

    public function test_item_is_edited_hidden_from_the_list_and_deleted(): void
    {
        $item = HeroItem::factory()->create(['name' => $this->tr('Курага'), 'weight_g' => 350, 'unit_price' => 120000]);

        Livewire::test(HeroItems\Form::class, ['item' => $item])
            ->assertSet('form.weight_kg', '0.350')
            ->set('form.unit_price', 130000)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(130000, $item->refresh()->unit_price);

        Livewire::test(HeroItems\Index::class)
            ->assertSee('Курага')
            ->assertSee("45\u{00A0}500")
            ->call('togglePublished', $item->id);
        $this->assertFalse($item->refresh()->is_published);

        Livewire::test(HeroItems\Index::class)->call('delete', $item->id);
        $this->assertSame(0, HeroItem::count());
        $this->assertSame(1, ActivityLog::where('action', 'deleted')->where('subject_type', HeroItem::class)->count());
    }

    public function test_screens_use_no_currency_or_price_wording(): void
    {
        $item = HeroItem::factory()->create();

        foreach (['/admin/hero-items', "/admin/hero-items/{$item->id}/edit"] as $url) {
            $html = mb_strtolower($this->get($url)->getContent());
            foreach (['сум', 'soʻm', 'цена', 'цены', 'стоимость'] as $word) {
                $this->assertStringNotContainsString($word, $html, "{$url} mentions «{$word}»");
            }
        }
    }
}
