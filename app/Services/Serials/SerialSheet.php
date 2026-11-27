<?php

namespace App\Services\Serials;

use App\Models\ScaleModel;
use App\Models\SerialNumber;
use DateTimeImmutable;

/**
 * Checks the rows of a file of imported scales before anything is saved. The file is read in
 * the browser (resources/js/serial-sheet.js) and arrives here as rows, in portions.
 * Columns, in this order: serial number, model, import date.
 */
class SerialSheet
{
    public const MAX_ROWS = 20000;

    /** How many rows the browser sends in one portion. */
    public const CHUNK = 500;

    public const DATE_FORMATS = ['d.m.Y', 'j.n.Y', 'Y-m-d', 'd/m/Y', 'd-m-Y'];

    /**
     * Sorts rows into those that can be loaded and those that cannot, with the reason. A number
     * already saved in the batch being loaded is a repeat inside the file, from an earlier portion.
     *
     * @param  list<array{line: int, cells: list<mixed>}>  $rows
     * @return array{
     *     valid: list<array{line: int, serial: string, scale_model_id: int, model_text: string, imported_on: string}>,
     *     problems: list<array{line: int, serial: string, reason: string}>
     * }
     */
    public function check(array $rows, ?int $batchId = null): array
    {
        $models = $this->modelIndex();
        $today = now()->format('Y-m-d');
        $valid = [];
        $problems = [];
        $seen = [];

        foreach ($rows as ['line' => $line, 'cells' => $cells]) {
            $serial = SerialNumber::normalize($this->text($cells[0] ?? ''));
            $modelText = trim($this->text($cells[1] ?? ''));
            $date = $this->date($cells[2] ?? null);
            $matches = $models[self::modelKey($modelText)] ?? [];

            $reason = match (true) {
                $serial === '' => 'Нет серийного номера.',
                mb_strlen($serial) < 4 || mb_strlen($serial) > 64 => 'Серийный номер: от 4 до 64 знаков.',
                ! preg_match('/^[A-Z0-9][A-Z0-9\-_.\/]*$/', $serial) => 'Серийный номер: только латинские буквы, цифры и знаки - _ . /',
                $modelText === '' => 'Не указана модель.',
                $matches === [] => "Модель «{$modelText}» не найдена среди моделей сайта.",
                count($matches) > 1 => "«{$modelText}» подходит к нескольким моделям: укажите точнее, например RLS1100A.",
                $date === null => 'Дата не распознана. Нужен вид 31.12.2026 или 2026-12-31.',
                $date > $today => 'Дата импорта в будущем.',
                isset($seen[$serial]) => "Повтор в файле: такой же номер в строке {$seen[$serial]}.",
                default => null,
            };

            if ($reason !== null) {
                $problems[] = ['line' => $line, 'serial' => mb_substr($serial, 0, 64), 'reason' => $reason];

                continue;
            }

            $seen[$serial] = $line;
            $valid[$serial] = [
                'line' => $line,
                'serial' => $serial,
                'scale_model_id' => $matches[0],
                'model_text' => mb_substr($modelText, 0, 80),
                'imported_on' => $date,
            ];
        }

        foreach (array_chunk(array_keys($valid), 1000) as $chunk) {
            foreach (SerialNumber::whereIn('serial', array_map('strval', $chunk))->pluck('serial_batch_id', 'serial') as $existing => $inBatch) {
                $reason = $batchId !== null && (int) $inBatch === $batchId ? 'Повтор в файле: такой же номер есть выше.' : 'Этот номер уже загружен раньше.';
                $problems[] = ['line' => $valid[$existing]['line'], 'serial' => (string) $existing, 'reason' => $reason];
                unset($valid[$existing]);
            }
        }

        usort($problems, fn (array $a, array $b) => $a['line'] <=> $b['line']);

        return ['valid' => array_values($valid), 'problems' => $problems];
    }

    /**
     * Every way a model may be written in a file => ids of the models it can mean. A model
     * named "RLS1515/RLS1530" answers to the full name, to each half and to its site slug.
     *
     * @return array<string, list<int>>
     */
    private function modelIndex(): array
    {
        $index = [];
        foreach (ScaleModel::get(['id', 'slug', 'name']) as $model) {
            $names = [$model->name, $model->slug, ...explode('/', $model->name)];
            foreach ($names as $name) {
                foreach ([$name, preg_replace('/^\s*AURORA\b/i', '', $name)] as $variant) {
                    $key = self::modelKey($variant);
                    if ($key !== '' && ! in_array($model->id, $index[$key] ?? [], true)) {
                        $index[$key][] = $model->id;
                    }
                }
            }
        }

        return $index;
    }

    /** "Aurora S2L-AI" => "AURORAS2LAI". */
    public static function modelKey(string $name): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', mb_strtoupper($name));
    }

    /** A cell as text; Excel hands whole numbers over as int or float. */
    private function text(mixed $cell): string
    {
        return match (true) {
            is_int($cell) => (string) $cell,
            is_float($cell) => floor($cell) === $cell ? sprintf('%.0f', $cell) : (string) $cell,
            is_string($cell) => trim($cell),
            default => '',
        };
    }

    /** Date text or Excel's day count as "Y-m-d"; null when it is not a real date. */
    private function date(mixed $cell): ?string
    {
        // A date cell without a date format arrives as Excel's day count (1 = 1900-01-01); accept 1970..2099.
        if ((is_int($cell) || is_float($cell)) && $cell >= 25569 && $cell < 73051) {
            return (new DateTimeImmutable('1899-12-30'))->modify('+'.(int) $cell.' days')->format('Y-m-d');
        }

        $text = $this->text($cell);
        foreach (self::DATE_FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $text);
            if ($date !== false && $date->format($format) === $text) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
