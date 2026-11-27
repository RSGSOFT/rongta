<?php

namespace App\Livewire\Admin\Serials;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\ScaleModel;
use App\Models\SerialBatch;
use App\Models\SerialNumber;
use App\Services\Serials\SerialSheet;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * /admin/serials/import (admins only). The Excel or CSV file is read in the browser and never
 * uploaded (resources/js/serial-import.js); its rows arrive here in portions: check() for the
 * preview, then start(), add() for each portion and finish() to save them as one batch.
 * Every row is checked here again, whatever the browser showed.
 */
class Import extends Component
{
    use LogsActivity, RequiresStaff;

    public const MAX_KB = 5 * 1024;

    protected bool $adminOnly = true;

    /** The batch being filled between start() and finish(). */
    #[Locked]
    public ?int $batchId = null;

    /**
     * Checks one portion of rows for the preview. Saves nothing.
     *
     * @return array{valid: list<array>, problems: list<array>}
     */
    #[Renderless]
    public function check(array $rows): array
    {
        $checked = app(SerialSheet::class)->check($this->clean($rows));
        $names = ScaleModel::pluck('name', 'id');

        return [
            'valid' => array_map(fn (array $row) => [...$row, 'model' => $names[$row['scale_model_id']]], $checked['valid']),
            'problems' => $checked['problems'],
        ];
    }

    /** Opens an empty batch for the rows that follow. */
    #[Renderless]
    public function start(string $fileName): bool
    {
        $this->batchId = SerialBatch::create([
            'user_id' => auth()->id(),
            'file_name' => trim(mb_substr($fileName, 0, 255)) ?: 'file',
            'rows_total' => 0,
            'rows_added' => 0,
            'rows_skipped' => 0,
            'skipped' => null,
        ])->id;

        return true;
    }

    /**
     * Checks one portion and saves its good rows into the open batch.
     *
     * @return array{added: int, skipped: int}
     */
    #[Renderless]
    public function add(array $rows): array
    {
        $batch = $this->openBatch();
        $rows = $this->clean($rows);
        abort_if($batch->rows_total + count($rows) > SerialSheet::MAX_ROWS, 422);

        $checked = app(SerialSheet::class)->check($rows, $batch->id);

        DB::transaction(function () use ($batch, $checked) {
            $now = now();
            SerialNumber::insert(array_map(fn (array $row) => [
                'serial_batch_id' => $batch->id,
                'scale_model_id' => $row['scale_model_id'],
                'serial' => $row['serial'],
                'model_text' => $row['model_text'],
                'imported_on' => $row['imported_on'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $checked['valid']));

            $batch->update([
                'rows_total' => $batch->rows_total + count($checked['valid']) + count($checked['problems']),
                'rows_added' => $batch->rows_added + count($checked['valid']),
                'rows_skipped' => $batch->rows_skipped + count($checked['problems']),
                'skipped' => array_slice([...($batch->skipped ?? []), ...$checked['problems']], 0, SerialBatch::SKIPPED_KEPT) ?: null,
            ]);
        });

        return ['added' => count($checked['valid']), 'skipped' => count($checked['problems'])];
    }

    /**
     * Closes the batch. A batch that received no good rows is removed.
     *
     * @return array{batch: int|null, added: int, skipped: int, url: string|null}
     */
    #[Renderless]
    public function finish(): array
    {
        $batch = $this->openBatch();
        $this->batchId = null;

        if ($batch->rows_added === 0) {
            $batch->delete();

            return ['batch' => null, 'added' => 0, 'skipped' => $batch->rows_skipped, 'url' => null];
        }

        $this->logActivity('created', $batch, ['file_name' => $batch->file_name, 'rows_added' => $batch->rows_added, 'rows_skipped' => $batch->rows_skipped]);

        return [
            'batch' => $batch->id,
            'added' => $batch->rows_added,
            'skipped' => $batch->rows_skipped,
            'url' => route('admin.serials.batches.show', $batch),
        ];
    }

    private function openBatch(): SerialBatch
    {
        abort_if($this->batchId === null, 409);

        return SerialBatch::findOrFail($this->batchId);
    }

    /**
     * One portion as sent by the browser, reduced to line numbers and three plain cells.
     *
     * @return list<array{line: int, cells: list<int|float|string>}>
     */
    private function clean(array $rows): array
    {
        abort_if(count($rows) > SerialSheet::CHUNK, 422);

        return array_map(function (mixed $row): array {
            abort_unless(is_array($row) && is_int($row['line'] ?? null) && $row['line'] > 0 && is_array($row['cells'] ?? null), 422);

            return [
                'line' => $row['line'],
                'cells' => array_map(
                    fn (mixed $cell) => match (true) {
                        is_string($cell) => mb_substr($cell, 0, 200),
                        is_int($cell), is_float($cell) => $cell,
                        default => '',
                    },
                    array_slice(array_values($row['cells']), 0, 3),
                ),
            ];
        }, array_values($rows));
    }

    public function render(): View
    {
        return view('livewire.admin.serials.import', [
            'maxMb' => self::MAX_KB / 1024,
            'maxRows' => SerialSheet::MAX_ROWS,
            'config' => ['maxBytes' => self::MAX_KB * 1024, 'maxRows' => SerialSheet::MAX_ROWS, 'chunk' => SerialSheet::CHUNK],
        ])->layout('components.layouts.admin', ['title' => 'Загрузка серийных номеров']);
    }
}
