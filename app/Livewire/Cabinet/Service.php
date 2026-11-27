<?php

namespace App\Livewire\Cabinet;

use App\Livewire\Cabinet\Concerns\RequiresCabinet;
use App\Models\SerialDetailRequest;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use App\Models\ServiceCase;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Service section of the cabinet. A service centre finds a scale by its serial number, sees its
 * warranty and records service steps. The full history is shown only for scales this centre has
 * serviced or RSG has opened to it; for any other scale only "official import", the model and
 * the warranty (what the public check shows), with a way to ask RSG for the rest.
 */
class Service extends Component
{
    use RequiresCabinet;

    /** How many recently serviced scales the list shows. */
    public const RECENT = 30;

    protected string $needs = 'service';

    public string $serial = '';

    /** The number that was looked up, normalised; empty before the first search. */
    #[Locked]
    public string $checked = '';

    #[Locked]
    public ?int $foundId = null;

    public array $form = [];

    public string $requestNote = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->form = ['type' => 'service_in', 'happened_on' => now()->format('Y-m-d'), 'service_case_id' => '', 'note' => ''];
    }

    public function find(): void
    {
        $this->resetErrorBag();
        $this->saved = false;
        $this->checked = '';
        $this->foundId = null;
        $this->requestNote = '';
        $this->resetForm();

        $serial = SerialNumber::normalize(mb_substr($this->serial, 0, 64));
        if ($serial === '') {
            $this->addError('serial', ui('sn.err.required'));

            return;
        }

        $this->checked = $serial;
        $this->foundId = SerialNumber::where('serial', $serial)->value('id');
        $this->js("document.querySelector('#cab-scale')?.focus()");
    }

    /** Opens a scale from the "serviced by you" list. */
    public function open(string $serial): void
    {
        $this->serial = $serial;
        $this->find();
    }

    private function scale(): ?SerialNumber
    {
        return $this->foundId
            ? SerialNumber::with(['scaleModel', 'movements.partner', 'movements.serviceCase'])->find($this->foundId)
            : null;
    }

    /** This centre has recorded a service step for the scale. */
    private function servicedHere(SerialNumber $scale): bool
    {
        $partnerId = $this->partner()->id;

        return $scale->movements->contains(fn (SerialMovement $m) => $m->partner_id === $partnerId && in_array($m->type, SerialMovement::SERVICE_TYPES, true));
    }

    private function latestRequest(SerialNumber $scale): ?SerialDetailRequest
    {
        return SerialDetailRequest::where('partner_id', $this->partner()->id)
            ->where('serial_number_id', $scale->id)
            ->latest('id')
            ->first();
    }

    public function addStep(): void
    {
        $this->saved = false;
        $scale = $this->scale();
        abort_if($scale === null, 404);

        $validator = Validator::make($this->form, [
            'type' => ['required', Rule::in(SerialMovement::SERVICE_TYPES)],
            'happened_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$scale->imported_on->format('Y-m-d'), 'before_or_equal:today'],
            'service_case_id' => ['nullable', Rule::exists('service_cases', 'id')],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'type.*' => ui('pf.err.choice'),
            'happened_on.*' => ui('cab.svc.err.date'),
            'service_case_id.*' => ui('pf.err.choice'),
            'note.*' => ui('pf.err.long'),
        ]);

        $this->resetErrorBag();
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                $this->addError('form.'.$field, $messages[0]);
            }

            return;
        }

        $scale->movements()->create([
            'type' => $this->form['type'],
            'partner_id' => $this->partner()->id,
            'cabinet_account_id' => $this->account()->id,
            'service_case_id' => $this->form['service_case_id'] !== '' ? (int) $this->form['service_case_id'] : null,
            'happened_on' => $this->form['happened_on'],
            'note' => trim((string) $this->form['note']) ?: null,
        ]);

        $this->resetForm();
        $this->saved = true;
    }

    public function requestDetails(): void
    {
        $scale = $this->scale();
        abort_if($scale === null, 404);

        // Nothing to ask for when the history is already open, or a request is still waiting.
        if ($this->servicedHere($scale) || in_array($this->latestRequest($scale)?->status, ['new', SerialDetailRequest::GRANTED], true)) {
            return;
        }

        $note = trim($this->requestNote);
        if (mb_strlen($note) > 500) {
            $this->addError('requestNote', ui('pf.err.long'));

            return;
        }

        SerialDetailRequest::create([
            'partner_id' => $this->partner()->id,
            'cabinet_account_id' => $this->account()->id,
            'serial_number_id' => $scale->id,
            'note' => $note ?: null,
            'status' => 'new',
        ]);
        $this->requestNote = '';
    }

    public function render(): View
    {
        $scale = $this->scale();
        $partnerId = $this->partner()->id;
        $request = $scale ? $this->latestRequest($scale) : null;

        return view('livewire.cabinet.service', [
            'scale' => $scale,
            'full' => $scale && ($this->servicedHere($scale) || $request?->status === SerialDetailRequest::GRANTED),
            'request' => $request,
            'months' => SerialNumber::warrantyMonths(),
            'cases' => $scale
                ? ServiceCase::where(fn ($q) => $q->whereNull('family_id')->orWhere('family_id', $scale->scaleModel->family_id))->orderBy('sort')->orderBy('id')->get()
                : collect(),
            'recent' => SerialNumber::query()
                ->whereHas('movements', fn ($q) => $q->where('partner_id', $partnerId)->whereIn('type', SerialMovement::SERVICE_TYPES))
                ->with('scaleModel')
                ->withMax(['movements as last_service' => fn ($q) => $q->where('partner_id', $partnerId)->whereIn('type', SerialMovement::SERVICE_TYPES)], 'happened_on')
                ->orderByDesc('last_service')
                ->limit(self::RECENT)
                ->get(),
        ]);
    }
}
