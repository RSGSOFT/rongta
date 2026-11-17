<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Cities;
use App\Livewire\Admin\Partners;
use App\Livewire\Admin\Regions;
use App\Models\ActivityLog;
use App\Models\City;
use App\Models\Partner;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class RegionsAndPartnersTest extends TestCase
{
    use MakesContent, MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    public function test_region_is_created_and_shown_on_the_map_preview(): void
    {
        $this->region();

        Livewire::test(Regions\Form::class)
            ->set('form.slug', 'samarkand')
            ->set('form.name', $this->tr('Самаркандская область'))
            ->set('form.short', ['ru' => 'Самарканд', 'uz' => 'Samarqand', 'en' => 'Samarkand'])
            ->set('form.map_col', 3)
            ->set('form.map_row', 2)
            ->assertSee('Клетка свободна: колонка 3, ряд 2')
            ->assertSeeHtml('grid-column: 3; grid-row: 2')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.regions.index'));

        $region = Region::where('slug', 'samarkand')->firstOrFail();
        $this->assertSame([3, 2], [$region->map_col, $region->map_row]);
        $this->assertSame('Samarqand', $region->short['uz']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_type' => Region::class, 'subject_id' => $region->id]);
    }

    public function test_region_validation_and_taken_map_tile(): void
    {
        $this->region();

        Livewire::test(Regions\Form::class)
            ->set('form.slug', 'tashkent-city')
            ->set('form.name', ['ru' => '', 'uz' => 'x', 'en' => 'x'])
            ->set('form.short', $this->tr('Т'))
            ->set('form.map_col', 9)
            ->set('form.map_row', 0)
            ->assertSee('Клетка вне карты')
            ->call('save')
            ->assertHasErrors(['form.slug' => 'unique', 'form.name.ru' => 'required', 'form.map_col' => 'max', 'form.map_row' => 'min']);

        Livewire::test(Regions\Form::class)
            ->set('form.slug', 'other')
            ->set('form.name', $this->tr('Другая'))
            ->set('form.short', $this->tr('Др'))
            ->set('form.map_col', 6)
            ->set('form.map_row', 1)
            ->assertSee('Клетка занята регионом')
            ->call('save')
            ->assertHasErrors('form.map_row');

        $this->assertSame(1, Region::count());
    }

    public function test_region_edit_keeps_its_own_tile(): void
    {
        $region = $this->region();

        Livewire::test(Regions\Form::class, ['region' => $region])
            ->assertSet('form.map_col', 6)
            ->set('form.name.en', 'Tashkent city')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Tashkent city', $region->fresh()->name['en']);
        $this->assertSame(['name'], array_keys(ActivityLog::where('action', 'updated')->firstOrFail()->changes));
    }

    public function test_region_with_partners_or_cities_cannot_be_deleted(): void
    {
        $used = $this->region();
        $this->partner($used);
        $withCity = $this->region(['slug' => 'andijan', 'map_col' => 8]);
        $this->city($withCity);
        $empty = $this->region(['slug' => 'navoi', 'map_col' => 2]);

        Livewire::test(Regions\Index::class)->call('delete', $used->id)->assertSee('Нельзя удалить регион, в котором есть партнёры');
        Livewire::test(Regions\Index::class)->call('delete', $withCity->id)->assertSee('Нельзя удалить регион, в котором есть города');
        Livewire::test(Regions\Index::class)->call('delete', $empty->id);

        $this->assertNotNull($used->fresh());
        $this->assertNotNull($withCity->fresh());
        $this->assertNull($empty->fresh());
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_type' => Region::class, 'subject_id' => $empty->id]);
    }

    public function test_city_crud_filter_and_delete_guard(): void
    {
        $region = $this->region();
        $other = $this->region(['slug' => 'andijan', 'name' => $this->tr('Андижан'), 'map_col' => 8]);

        Livewire::test(Cities\Form::class)
            ->set('form.slug', 'chilanzar')
            ->set('form.name', $this->tr('Чиланзар'))
            ->call('save')
            ->assertHasErrors(['form.region_id' => 'required']);

        Livewire::test(Cities\Form::class)
            ->set('form.slug', 'chilanzar')
            ->set('form.name', $this->tr('Чиланзар'))
            ->set('form.region_id', $region->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.cities.index'));

        $city = City::firstOrFail();
        $this->assertSame($region->id, $city->region_id);

        Livewire::test(Cities\Form::class)->set('form.slug', 'chilanzar')->set('form.name', $this->tr('x'))->set('form.region_id', $region->id)
            ->call('save')->assertHasErrors(['form.slug' => 'unique']);

        $this->city($other, ['slug' => 'asaka', 'name' => $this->tr('Асака')]);
        Livewire::test(Cities\Index::class)->set('filterRegion', (string) $other->id)->assertSee('Асака')->assertDontSee('Чиланзар');

        // A partner follows its city to another region.
        $partner = $this->partner($region, ['city_id' => $city->id]);
        Livewire::test(Cities\Form::class, ['city' => $city])->set('form.region_id', $other->id)->call('save')->assertHasNoErrors();
        $this->assertSame($other->id, $partner->fresh()->region_id);

        Livewire::test(Cities\Index::class)->call('delete', $city->id)->assertSee('Нельзя удалить город');
        $this->assertNotNull($city->fresh());

        $partner->delete();
        Livewire::test(Cities\Index::class)->call('delete', $city->id);
        $this->assertNull($city->fresh());
    }

    public function test_partner_is_created_with_contacts_and_offers(): void
    {
        $region = $this->region();
        $city = $this->city($region);

        Livewire::test(Partners\Form::class)
            ->assertSet('form.is_published', true)
            ->assertSet('form.is_placeholder', false)
            ->set('form.kind', 'integrator')
            ->set('form.name', $this->tr('Софт Плюс'))
            ->set('form.region_id', $region->id)
            ->set('form.city_id', $city->id)
            ->set('form.offers', ['support', 'integration'])
            ->set('form.phone', ' +998 71 200 00 00 ')
            ->set('form.telegram', 'soft_plus')
            ->set('form.address', ['ru' => 'ул. Навои, 1', 'uz' => '', 'en' => ''])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.partners.index'));

        $partner = Partner::firstOrFail();
        $this->assertSame('integrator', $partner->kind);
        $this->assertSame(['integration', 'support'], $partner->offers);
        $this->assertSame('+998 71 200 00 00', $partner->phone);
        $this->assertSame('@soft_plus', $partner->telegram);
        $this->assertSame('ул. Навои, 1', $partner->address['ru']);
        $this->assertNull($partner->slug);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_type' => Partner::class]);

        // The real partner is on the public Dealers page with its phone.
        $this->get('/dealers?tab=integrator')->assertOk()->assertSee('Софт Плюс')->assertSee('+998 71 200 00 00');
    }

    public function test_partner_validation(): void
    {
        $region = $this->region();
        $other = $this->region(['slug' => 'andijan', 'map_col' => 8]);
        $foreignCity = $this->city($other);

        Livewire::test(Partners\Form::class)
            ->set('form.kind', 'dealer')
            ->set('form.region_id', $region->id)
            ->set('form.city_id', $foreignCity->id)
            ->set('form.offers', ['sales', 'discount'])
            ->set('form.phone', 'call me')
            ->set('form.telegram', 'https://t.me/x y')
            ->call('save')
            ->assertHasErrors(['form.kind' => 'in', 'form.name.ru' => 'required', 'form.city_id', 'form.offers.1' => 'in', 'form.phone' => 'regex', 'form.telegram' => 'regex'])
            ->assertNoRedirect();

        Livewire::test(Partners\Form::class)->set('form.name', $this->tr('Без региона'))->call('save')->assertHasErrors(['form.region_id' => 'required']);

        $this->assertSame(0, Partner::count());
    }

    public function test_sample_partner_is_replaced_by_a_real_one(): void
    {
        $region = $this->region();
        $sample = $this->partner($region, ['slug' => 'sample-1', 'name' => $this->tr('Образец 1'), 'is_placeholder' => true]);

        Livewire::test(Partners\Form::class, ['partner' => $sample])
            ->assertSee('Это образец из прототипа')
            ->set('form.name', $this->tr('Настоящий дилер'))
            ->set('form.phone', '+998901112233')
            ->set('form.is_placeholder', false)
            ->call('save')
            ->assertHasNoErrors();

        $sample->refresh();
        $this->assertFalse($sample->is_placeholder);
        $this->assertSame('Настоящий дилер', $sample->name['ru']);
    }

    public function test_partner_list_filters_toggle_and_delete(): void
    {
        $region = $this->region();
        $other = $this->region(['slug' => 'andijan', 'name' => $this->tr('Андижан'), 'map_col' => 8]);
        $reseller = $this->partner($region, ['name' => $this->tr('Реселлер А')]);
        $this->partner($other, ['kind' => 'integrator', 'name' => $this->tr('Интегратор Б'), 'telegram' => '@integb', 'is_published' => false]);
        $this->partner($region, ['name' => $this->tr('Пример В'), 'is_placeholder' => true]);

        Livewire::test(Partners\Index::class)->assertSee('Реселлер А')->assertSee('Интегратор Б')->assertSee('Пример В')
            ->set('filterKind', 'integrator')->assertSee('Интегратор Б')->assertDontSee('Реселлер А')
            ->set('filterKind', '')->set('filterRegion', (string) $region->id)->assertSee('Реселлер А')->assertDontSee('Интегратор Б')
            ->set('filterRegion', '')->set('filterStatus', 'hidden')->assertSee('Интегратор Б')->assertDontSee('Реселлер А')
            ->set('filterStatus', 'sample')->assertSee('Пример В')->assertDontSee('Реселлер А')
            ->set('filterStatus', 'real')->assertSee('Реселлер А')->assertDontSee('Пример В')
            ->set('filterStatus', '')->set('search', 'INTEGB')->assertSee('Интегратор Б')->assertDontSee('Реселлер А');

        Livewire::test(Partners\Index::class)->call('togglePublished', $reseller->id);
        $this->assertFalse($reseller->fresh()->is_published);
        $this->assertDatabaseHas('activity_logs', ['action' => 'unpublished', 'subject_id' => $reseller->id]);

        Livewire::test(Partners\Index::class)->call('delete', $reseller->id)->assertSee('Партнёр удалён.');
        $this->assertNull($reseller->fresh());
    }

    public function test_partner_list_has_no_sample_tools(): void
    {
        $this->partner($this->region(), ['name' => $this->tr('Образец 1'), 'is_placeholder' => true]);

        Livewire::test(Partners\Index::class)
            ->assertSee('Образец 1')
            ->assertDontSee('Образцы из прототипа')
            ->assertDontSee('Удалить все образцы');
    }
}
