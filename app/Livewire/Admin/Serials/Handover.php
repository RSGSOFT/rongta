<?php

namespace App\Livewire\Admin\Serials;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Partner;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * /admin/serials/handover (admins only): hand many scales to one dealer at once by pasting their
 * serial numbers. The handover date starts each scale's warranty.
 */
class Handover extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation;

    public const MAX_NUMBERS = 2000;

    protected bool $adminOnly = true;

    public string $partnerId = '';

    public string $happenedOn = '';

    public string $serials = '';

    /** @var array{done: int, problems: list<array{serial: string, reason: string}>}|null */
    public ?array $report = null;

    public function mount(): void
    {
        $this->happenedOn = now()->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'partnerId' => ['required', Rule::exists('partners', 'id')->where(fn ($query) => $query->whereJsonContains('offers', 'sales'))],
            'happenedOn' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'serials' => ['required', 'string', 'max:200000'],
        ];
    }

    protected function messages(): array
    {
        return [...$this->russianMessages(), 'happenedOn.before_or_equal' => 'Дата не может быть в будущем.'];
    }

    protected function validationAttributes(): array
    {
        return ['partnerId' => 'Дилер', 'happenedOn' => 'Дата передачи', 'serials' => 'Серийные номера'];
    }

    public function submit(): void
    {
        $this->report = null;
        $this->validate();

        $wanted = collect(preg_split('/[\s,;]+/u', $this->serials, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $serial) => SerialNumber::normalize($serial))
            ->unique()
            ->values();

        if ($wanted->count() > self::MAX_NUMBERS) {
            $this->addError('serials', 'Не больше '.self::MAX_NUMBERS.' номеров за один раз.');

            return;
        }

        $found = SerialNumber::with(['movements' => fn ($query) => $query->where('type', SerialMovement::DEALER)->with('partner')])
            ->whereIn('serial', $wanted)
            ->get()
            ->keyBy('serial');

        $problems = [];
        $rows = [];
        $now = now();
        foreach ($wanted as $serial) {
            $item = $found->get($serial);
            $reason = match (true) {
                $item === null => 'Нет в базе серийных номеров.',
                $item->movements->isNotEmpty() => 'Уже передан дилеру: '.$item->movements->first()->partner?->tr('name', 'ru').', '.$item->movements->first()->happened_on->format('d.m.Y').'.',
                $item->imported_on->format('Y-m-d') > $this->happenedOn => 'Дата передачи раньше даты импорта ('.$item->imported_on->format('d.m.Y').').',
                default => null,
            };
            if ($reason !== null) {
                $problems[] = ['serial' => mb_substr($serial, 0, 64), 'reason' => $reason];

                continue;
            }
            $rows[] = [
                'serial_number_id' => $item->id,
                'type' => SerialMovement::DEALER,
                'partner_id' => (int) $this->partnerId,
                'happened_on' => $this->happenedOn,
                'user_id' => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                SerialMovement::insert($chunk);
            }
        });

        if ($rows !== []) {
            $this->logActivity('updated', Partner::find((int) $this->partnerId), ['serials_handed_over' => count($rows), 'happened_on' => $this->happenedOn]);
        }

        $this->report = ['done' => count($rows), 'problems' => $problems];
        if ($problems === []) {
            $this->serials = '';
        }
    }

    public function render(): View
    {
        return view('livewire.admin.serials.handover', [
            'dealers' => Partner::sellers()->orderBy('sort')->orderBy('id')->get()->mapWithKeys(fn (Partner $p) => [$p->id => $p->tr('name', 'ru')])->all(),
            'months' => SerialNumber::warrantyMonths(),
        ])->layout('components.layouts.admin', ['title' => 'Передача весов дилеру']);
    }
}
