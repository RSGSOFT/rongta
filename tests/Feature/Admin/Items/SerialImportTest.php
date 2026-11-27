<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Models\Index as ModelsIndex;
use App\Livewire\Admin\Serials;
use App\Models\ActivityLog;
use App\Models\ScaleModel;
use App\Models\SerialBatch;
use App\Models\SerialNumber;
use App\Services\Serials\SerialSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class SerialImportTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    /** @var array<string, ScaleModel> */
    private array $models = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-24 10:00:00');

        $family = $this->family();
        foreach (['rls1000a' => 'RLS1000/RLS1100A', 'rls1000b' => 'RLS1000/RLS1100B', 'rls1515' => 'RLS1515/RLS1530', 'aurora-s2l-ai' => 'AURORA S2L-AI', 'c11' => 'C11'] as $slug => $name) {
            $this->models[$slug] = $this->scaleModel($family, ['slug' => $slug, 'name' => $name]);
        }

        $this->actingAs($this->admin(['name' => 'Admin One']));
    }

    /**
     * Rows as the browser sends them after reading the file: line numbers and three cells.
     *
     * @param  list<list<mixed>>  $lines  data rows; the header was line 1
     * @return list<array{line: int, cells: list<mixed>}>
     */
    private function rows(array $lines, int $firstLine = 2): array
    {
        return array_map(fn (array $cells, int $index) => ['line' => $firstLine + $index, 'cells' => $cells], $lines, array_keys($lines));
    }

    public function test_only_admins_open_the_serial_screens(): void
    {
        $batch = SerialBatch::factory()->create();
        $urls = ['/admin/serials', '/admin/serials/import', "/admin/serials/batches/{$batch->id}"];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $this->actingAs($this->editor());
        foreach ($urls as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/admin')->assertDontSee(route('admin.serials.index'));
    }

    public function test_screen_reads_the_file_in_the_browser_and_has_no_upload(): void
    {
        $this->get('/admin/serials/import')
            ->assertOk()
            ->assertSee('id="serial-file"', false)
            ->assertSee('x-on:change="pick($event)"', false)
            ->assertDontSee('wire:model="file"', false);
    }

    public function test_rows_are_checked_first_and_saved_only_on_load(): void
    {
        $rows = $this->rows([
            ['rt1530-0001', 'RLS1530', '01.10.2026'],
            ['RT1100-0002', 'rls1100a', '2026-10-02'],
            ['S2L-0003', 'Aurora S2L-AI', '3.10.2026'],
        ]);

        $page = Livewire::test(Serials\Import::class)->call('check', $rows)->assertReturned(fn (array $checked) => $checked['problems'] === []
            && array_column($checked['valid'], 'model', 'serial') === ['RT1530-0001' => 'RLS1515/RLS1530', 'RT1100-0002' => 'RLS1000/RLS1100A', 'S2L-0003' => 'AURORA S2L-AI']);

        $this->assertSame(0, SerialBatch::count() + SerialNumber::count());

        $page->call('start', 'scales.csv')
            ->call('add', $rows)->assertReturned(['added' => 3, 'skipped' => 0])
            ->call('finish')->assertReturned(fn (array $done) => [$done['added'], $done['skipped']] === [3, 0] && $done['url'] === route('admin.serials.batches.show', $done['batch']))
            ->assertSet('batchId', null);

        $batch = SerialBatch::sole();
        $this->assertSame(['scales.csv', 3, 3, 0, null], [$batch->file_name, $batch->rows_total, $batch->rows_added, $batch->rows_skipped, $batch->skipped]);
        $this->assertSame('Admin One', $batch->user->name);

        $first = SerialNumber::where('serial', 'RT1530-0001')->sole();
        $this->assertSame([$batch->id, $this->models['rls1515']->id, 'RLS1530', '2026-10-01'], [$first->serial_batch_id, $first->scale_model_id, $first->model_text, $first->imported_on->format('Y-m-d')]);
        $this->assertSame($this->models['rls1000a']->id, SerialNumber::where('serial', 'RT1100-0002')->value('scale_model_id'));
        $this->assertSame('2026-10-03', SerialNumber::where('serial', 'S2L-0003')->sole()->imported_on->format('Y-m-d'));
        $this->assertSame(1, ActivityLog::where('action', 'created')->where('subject_type', SerialBatch::class)->count());
    }

    public function test_bad_rows_are_reported_and_only_good_rows_are_loaded(): void
    {
        SerialNumber::factory()->create(['serial' => 'OLD-0001', 'scale_model_id' => $this->models['c11']->id]);

        $rows = $this->rows([
            ['NEW-0001', 'C11', '01.10.2026'],       // 2 good
            ['NEW-0002', 'RLS1000', '01.10.2026'],   // 3 two models answer to "RLS1000"
            ['NEW-0003', 'RLS9999', '01.10.2026'],   // 4 unknown model
            ['NEW-0004', 'C11', '31.02.2026'],       // 5 not a date
            ['NEW-0005', 'C11', '25.11.2026'],       // 6 tomorrow
            ['new-0001', 'C11', '02.10.2026'],       // 7 repeats line 2
            ['OLD-0001', 'C11', '01.10.2026'],       // 8 already loaded
            ['', 'C11', '01.10.2026'],               // 9 no serial
            ['НОМЕР-1', 'C11', '01.10.2026'],        // 10 Cyrillic
            ['NEW-0006', '', '01.10.2026'],          // 11 no model
        ]);

        $reasons = collect();
        $page = Livewire::test(Serials\Import::class)->call('check', $rows)->assertReturned(function (array $checked) use (&$reasons) {
            $reasons = collect($checked['problems'])->pluck('reason', 'line');

            return array_column($checked['valid'], 'serial') === ['NEW-0001'];
        });

        $this->assertSame([3, 4, 5, 6, 7, 8, 9, 10, 11], $reasons->keys()->all());
        $this->assertStringContainsString('нескольким моделям', $reasons[3]);
        $this->assertStringContainsString('не найдена', $reasons[4]);
        $this->assertStringContainsString('Дата не распознана', $reasons[5]);
        $this->assertStringContainsString('в будущем', $reasons[6]);
        $this->assertStringContainsString('в строке 2', $reasons[7]);
        $this->assertStringContainsString('уже загружен', $reasons[8]);
        $this->assertStringContainsString('Нет серийного номера', $reasons[9]);
        $this->assertStringContainsString('латинские буквы', $reasons[10]);
        $this->assertStringContainsString('Не указана модель', $reasons[11]);

        $page->call('start', 'scales.csv')
            ->call('add', $rows)->assertReturned(['added' => 1, 'skipped' => 9])
            ->call('finish');

        $batch = SerialBatch::latest('id')->first();
        $this->assertSame(['NEW-0001'], $batch->serialNumbers()->pluck('serial')->all());
        $this->assertSame([10, 1, 9], [$batch->rows_total, $batch->rows_added, $batch->rows_skipped]);
        $this->assertCount(9, $batch->skipped);
        $this->assertSame(2, SerialNumber::count());

        $this->get("/admin/serials/batches/{$batch->id}")->assertOk()->assertSee('NEW-0001')->assertSee('нескольким моделям');
    }

    public function test_excel_numbers_are_read_as_serials_and_day_counts(): void
    {
        $rows = $this->rows([
            [2026100012345, 'C11', '2026-09-15'],   // a date cell arrives as Y-m-d
            ['S2L-777', 'S2L-AI', 46281],           // a date cell without a date format: Excel's day count
            ['S2L-778', 'S2L-AI', 12],
        ]);

        Livewire::test(Serials\Import::class)
            ->call('start', 'scales.xlsx')
            ->call('add', $rows)->assertReturned(['added' => 2, 'skipped' => 1]);

        $this->assertSame('2026-09-15', SerialNumber::where('serial', '2026100012345')->sole()->imported_on->format('Y-m-d'));
        $second = SerialNumber::where('serial', 'S2L-777')->sole();
        $this->assertSame([$this->models['aurora-s2l-ai']->id, '2026-09-16'], [$second->scale_model_id, $second->imported_on->format('Y-m-d')]);
    }

    public function test_portions_fill_one_batch_and_a_repeat_from_an_earlier_portion_is_skipped(): void
    {
        $page = Livewire::test(Serials\Import::class)
            ->call('start', 'big.csv')
            ->call('add', $this->rows([['AAA-0001', 'C11', '01.10.2026'], ['AAA-0002', 'C11', '01.10.2026']]))
            ->call('add', $this->rows([['aaa-0001', 'C11', '01.10.2026'], ['AAA-0003', 'C11', '01.10.2026']], 4))
            ->assertReturned(['added' => 1, 'skipped' => 1]);

        $batch = SerialBatch::sole();
        $this->assertSame([4, 3, 1], [$batch->rows_total, $batch->rows_added, $batch->rows_skipped]);
        $this->assertSame([['line' => 4, 'serial' => 'AAA-0001', 'reason' => 'Повтор в файле: такой же номер есть выше.']], $batch->skipped);
        $this->assertSame(0, ActivityLog::where('subject_type', SerialBatch::class)->count());

        $page->call('finish')->assertReturned(fn (array $done) => $done['batch'] === $batch->id && $done['added'] === 3);
        $this->assertSame(3, $batch->serialNumbers()->count());
    }

    public function test_load_with_no_good_rows_leaves_no_batch(): void
    {
        Livewire::test(Serials\Import::class)
            ->call('start', 'scales.csv')
            ->call('add', $this->rows([['AAA-0001', 'RLS9999', '01.10.2026']]))
            ->call('finish')
            ->assertReturned(['batch' => null, 'added' => 0, 'skipped' => 1, 'url' => null]);

        $this->assertSame(0, SerialBatch::count() + ActivityLog::count());
    }

    public function test_rows_are_refused_without_an_open_batch_or_in_a_wrong_shape(): void
    {
        $row = ['line' => 2, 'cells' => ['AAA-0001', 'C11', '01.10.2026']];

        Livewire::test(Serials\Import::class)->call('add', [$row])->assertStatus(409);
        Livewire::test(Serials\Import::class)->call('finish')->assertStatus(409);
        Livewire::test(Serials\Import::class)->call('check', array_fill(0, SerialSheet::CHUNK + 1, $row))->assertStatus(422);
        Livewire::test(Serials\Import::class)->call('check', [['line' => 'x', 'cells' => 'AAA-0001']])->assertStatus(422);

        Livewire::test(Serials\Import::class)
            ->call('check', [['line' => 2, 'cells' => [['AAA-0001'], 'C11', '01.10.2026', 'extra']]])
            ->assertReturned(fn (array $checked) => $checked['valid'] === [] && $checked['problems'][0]['reason'] === 'Нет серийного номера.');

        $this->assertSame(0, SerialBatch::count() + SerialNumber::count());
    }

    public function test_one_batch_holds_no_more_rows_than_the_file_limit(): void
    {
        $page = Livewire::test(Serials\Import::class)->call('start', 'huge.csv');
        SerialBatch::sole()->update(['rows_total' => SerialSheet::MAX_ROWS]);

        $page->call('add', $this->rows([['AAA-0001', 'C11', '01.10.2026']]))->assertStatus(422);

        $this->assertSame(0, SerialNumber::count());
    }

    public function test_serial_is_found_by_a_part_of_it_and_batches_are_listed(): void
    {
        $batch = SerialBatch::factory()->create(['file_name' => 'october.xlsx', 'rows_added' => 2]);
        SerialNumber::factory()->create(['serial_batch_id' => $batch->id, 'serial' => 'RT1530-0042', 'scale_model_id' => $this->models['rls1515']->id]);
        SerialNumber::factory()->create(['serial_batch_id' => $batch->id, 'serial' => 'C11-0099', 'scale_model_id' => $this->models['c11']->id]);

        Livewire::test(Serials\Index::class)
            ->assertSee('october.xlsx')
            ->assertDontSee('RT1530-0042')
            ->set('search', ' rt1530 ')
            ->assertSee('RT1530-0042')
            ->assertDontSee('C11-0099')
            ->set('search', 'nothing')
            ->assertSee('Такого серийного номера в базе нет.');
    }

    public function test_deleting_a_batch_removes_its_serial_numbers_and_is_logged(): void
    {
        $batch = SerialBatch::factory()->create();
        SerialNumber::factory()->count(2)->create(['serial_batch_id' => $batch->id, 'scale_model_id' => $this->models['c11']->id]);
        $kept = SerialNumber::factory()->create(['scale_model_id' => $this->models['c11']->id]);

        Livewire::test(Serials\Show::class, ['batch' => $batch])
            ->call('delete')
            ->assertRedirect(route('admin.serials.index'));

        $this->assertSame([$kept->id], SerialNumber::pluck('id')->all());
        $this->assertSame(1, ActivityLog::where('action', 'deleted')->where('subject_type', SerialBatch::class)->count());
    }

    public function test_model_with_serial_numbers_cannot_be_deleted(): void
    {
        SerialNumber::factory()->create(['scale_model_id' => $this->models['c11']->id]);

        Livewire::test(ModelsIndex::class)->call('delete', $this->models['c11']->id);

        $this->assertTrue(ScaleModel::whereKey($this->models['c11']->id)->exists());
        $this->assertSame(0, ActivityLog::where('action', 'deleted')->count());
    }
}
