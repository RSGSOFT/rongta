<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Catalog;
use App\Livewire\Admin\Items\Models\Specs;
use App\Models\ActivityLog;
use App\Models\ModelSpec;
use App\Models\UiString;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class SpecsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    private const TBC = ['ru' => 'уточняется', 'uz' => 'aniqlanmoqda', 'en' => 'to be confirmed'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    private function spec($model, string $key, array $value, string $group = 'weighing'): ModelSpec
    {
        return ModelSpec::create(['scale_model_id' => $model->id, 'group' => $group, 'key' => $key, 'value' => $value, 'sort' => 1]);
    }

    public function test_spec_keys_cover_every_group_of_the_model(): void
    {
        $this->assertSame(ModelSpec::GROUPS, array_keys(Catalog::SPEC_KEYS));
    }

    public function test_existing_specs_load_in_their_three_shapes(): void
    {
        $model = $this->scaleModel();
        $this->spec($model, 'accClass', $this->tr('III'));
        $this->spec($model, 'platter', self::TBC);
        $this->spec($model, 'printSpeed', ['v' => $this->tr('90 mm/s'), 'note' => $this->tr('unit to be confirmed')], 'printer');
        $this->spec($model, 'maxCap', ['parts' => [[$this->tr('15 kg version'), $this->tr('6 / 15 kg')], [$this->tr('30 kg version'), $this->tr('15 / 30 kg')]]]);

        Livewire::test(Specs::class, ['model' => $model])
            ->assertOk()
            ->assertSet('rows.accClass.on', true)
            ->assertSet('rows.accClass.shape', 'text')
            ->assertSet('rows.accClass.text.uz', 'III uz')
            ->assertSet('rows.platter.tbc', true)
            ->assertSet('rows.platter.text.ru', '')
            ->assertSet('rows.printSpeed.shape', 'note')
            ->assertSet('rows.printSpeed.text.ru', '90 mm/s')
            ->assertSet('rows.printSpeed.note.en', 'unit to be confirmed en')
            ->assertSet('rows.maxCap.shape', 'parts')
            ->assertSet('rows.maxCap.parts.1.label.ru', '30 kg version')
            ->assertSet('rows.maxCap.parts.1.value.ru', '15 / 30 kg')
            ->assertSet('rows.minCap.on', false);
    }

    public function test_saving_writes_each_shape_in_the_stored_format(): void
    {
        $model = $this->scaleModel();

        Livewire::test(Specs::class, ['model' => $model])
            // text
            ->set('rows.accClass.on', true)
            ->set('rows.accClass.text', ['ru' => ' III ', 'uz' => 'III', 'en' => 'III'])
            // to be confirmed
            ->set('rows.platter.on', true)
            ->set('rows.platter.tbc', true)
            // note
            ->set('rows.printSpeed.on', true)
            ->set('rows.printSpeed.shape', 'note')
            ->set('rows.printSpeed.text', $this->tr('90 мм/с'))
            ->set('rows.printSpeed.note', $this->tr('единица уточняется'))
            // parts: switching the shape offers two versions to fill in
            ->set('rows.maxCap.on', true)
            ->set('rows.maxCap.shape', 'parts')
            ->assertCount('rows.maxCap.parts', 2)
            ->set('rows.maxCap.parts.0.label', $this->tr('Версия 15 кг'))
            ->set('rows.maxCap.parts.0.value', $this->tr('6 / 15 кг'))
            ->set('rows.maxCap.parts.1.label', $this->tr('Версия 30 кг'))
            ->set('rows.maxCap.parts.1.value', $this->tr('15 / 30 кг'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Характеристики сохранены.');

        $specs = $model->specs()->get()->keyBy('key');
        $this->assertSame(['maxCap', 'accClass', 'platter', 'printSpeed'], $specs->keys()->all()); // catalogue order

        $this->assertSame('text', $specs['accClass']->shape());
        $this->assertSame(['ru' => 'III', 'uz' => 'III', 'en' => 'III'], $specs['accClass']->value);
        $this->assertSame('weighing', $specs['accClass']->group);

        $this->assertSame(self::TBC, $specs['platter']->value);

        $this->assertSame('note', $specs['printSpeed']->shape());
        $this->assertSame('printer', $specs['printSpeed']->group);
        $this->assertSame('90 мм/с', $specs['printSpeed']->text('ru'));
        $this->assertSame('единица уточняется en', $specs['printSpeed']->note('en'));

        $this->assertSame('parts', $specs['maxCap']->shape());
        $this->assertSame('Версия 15 кг: 6 / 15 кг; Версия 30 кг: 15 / 30 кг', $specs['maxCap']->text('ru'));

        $log = ActivityLog::where('action', 'updated')->firstOrFail();
        $this->assertSame($model->id, $log->subject_id);
        $this->assertEqualsCanonicalizing(['accClass', 'platter', 'printSpeed', 'maxCap'], $log->changes['specs']['saved']);
    }

    public function test_to_be_confirmed_uses_the_site_string_when_present(): void
    {
        UiString::create(['key' => 'val.tbc', 'ru' => 'уточняется у производителя', 'uz' => 'aniqlanmoqda', 'en' => 'to be confirmed']);
        $model = $this->scaleModel();

        Livewire::test(Specs::class, ['model' => $model])
            ->set('rows.netWeight.on', true)
            ->set('rows.netWeight.tbc', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('уточняется у производителя', $model->specs()->firstOrFail()->text('ru'));

        // And it is recognised again when the page is reopened.
        Livewire::test(Specs::class, ['model' => $model])->assertSet('rows.netWeight.tbc', true);
    }

    public function test_switching_a_spec_off_removes_the_row(): void
    {
        $model = $this->scaleModel();
        $this->spec($model, 'accClass', $this->tr('III'));
        $this->spec($model, 'minCap', $this->tr('40 g'));

        Livewire::test(Specs::class, ['model' => $model])
            ->set('rows.accClass.on', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['minCap'], $model->specs()->pluck('key')->all());
        $log = ActivityLog::where('action', 'updated')->firstOrFail();
        $this->assertSame(['removed' => ['accClass']], $log->changes['specs']);
    }

    public function test_saving_without_changes_logs_nothing(): void
    {
        $model = $this->scaleModel();
        $this->spec($model, 'accClass', $this->tr('III'));

        Livewire::test(Specs::class, ['model' => $model])->call('save')->assertHasNoErrors();

        $this->assertSame(0, ActivityLog::count());
        $this->assertSame($this->tr('III'), $model->specs()->firstOrFail()->value);
    }

    public function test_validation_requires_russian_values_only_for_enabled_rows(): void
    {
        $model = $this->scaleModel();

        Livewire::test(Specs::class, ['model' => $model])
            ->set('rows.accClass.on', true)                      // enabled, empty
            ->set('rows.printSpeed.on', true)
            ->set('rows.printSpeed.shape', 'note')
            ->set('rows.printSpeed.text', $this->tr('90'))       // note missing
            ->set('rows.maxCap.on', true)
            ->set('rows.maxCap.shape', 'parts')
            ->call('removePart', 'maxCap', 0)
            ->call('removePart', 'maxCap', 0)                    // no parts at all
            ->set('rows.minCap.on', true)
            ->set('rows.minCap.shape', 'parts')
            ->set('rows.minCap.parts.0.label', $this->tr('15 kg'))  // value missing
            ->set('rows.dims.shape', 'bogus')                    // off: ignored
            ->call('save')
            ->assertHasErrors([
                'rows.accClass.text.ru' => 'required',
                'rows.printSpeed.note.ru' => 'required',
                'rows.maxCap.parts' => 'required',
                'rows.minCap.parts.0.value.ru' => 'required',
            ])
            ->assertHasNoErrors(['rows.dims.shape', 'rows.dims.text.ru']);

        $this->assertSame(0, ModelSpec::count());

        Livewire::test(Specs::class, ['model' => $model])
            ->set('rows.accClass.on', true)
            ->set('rows.accClass.shape', 'bogus')
            ->call('save')
            ->assertHasErrors(['rows.accClass.shape' => 'in']);
    }

    public function test_custom_key_can_be_added_and_unknown_existing_keys_are_kept(): void
    {
        $model = $this->scaleModel();
        $this->spec($model, 'legacyKey', $this->tr('old'), 'general');

        $page = Livewire::test(Specs::class, ['model' => $model])
            ->assertSet('rows.legacyKey.on', true)
            ->set('newKey.power', 'bad key!')
            ->call('addKey', 'power')
            ->assertHasErrors('newKey.power')
            ->set('newKey.power', 'maxCap')
            ->call('addKey', 'power')
            ->assertHasErrors('newKey.power')
            ->set('newKey.power', 'battery')
            ->call('addKey', 'power')
            ->assertHasNoErrors()
            ->assertSet('rows.battery.on', true)
            ->set('rows.battery.text', $this->tr('6 V'))
            ->call('save')
            ->assertHasNoErrors();

        $specs = $model->specs()->get()->keyBy('key');
        $this->assertSame('power', $specs['battery']->group);
        $this->assertSame('general', $specs['legacyKey']->group);
        $this->assertSame($this->tr('old'), $specs['legacyKey']->value);

        $page->call('addKey', 'nonsense-group')->assertHasNoErrors();
    }
}
