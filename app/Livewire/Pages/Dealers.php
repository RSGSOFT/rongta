<?php

namespace App\Livewire\Pages;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dealers page: region map and list of published partners, plus the "Become a partner" form.
 * The region (slug) and the tab are kept in the query string: /dealers?tab=integrator&region=samarkand.
 */
class Dealers extends Component
{
    /** How many partner requests one address may send per hour. */
    public const REQUESTS_PER_HOUR = 5;

    /** Letters (any script), then letters, apostrophes, hyphens and spaces. Same rule as the prototype. */
    public const NAME_PATTERN = '/^\p{L}[\p{L}ʻʼ\'’\- ]*$/u';

    #[Url]
    public string $region = '';

    #[Url]
    public string $tab = 'reseller';

    public string $company = '';

    public string $contact_name = '';

    public string $phone = '+998 ';

    public string $form_region = '';

    public string $type = 'reseller';

    public string $message = '';

    /** Honeypot: hidden from people, so only a bot fills it in. */
    public string $website = '';

    public bool $sent = false;

    /** What the confirmation shows after a request is saved. */
    public array $summary = [];

    public function mount(): void
    {
        $this->normalise();
    }

    public function updated(string $name): void
    {
        if ($name === 'region' || $name === 'tab') {
            $this->normalise();
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->normalise();
    }

    public function setRegion(string $slug): void
    {
        $this->region = $slug;
        $this->normalise();
    }

    /** Unknown values from the URL fall back to "all regions" / "resellers". */
    protected function normalise(): void
    {
        if (! in_array($this->tab, Partner::KINDS, true)) {
            $this->tab = 'reseller';
        }
        if ($this->region !== '' && ! Region::where('slug', $this->region)->exists()) {
            $this->region = '';
        }
    }

    public static function cleanPhone(string $phone): string
    {
        return preg_replace('/[\s\-()]/', '', $phone);
    }

    public function submitRequest(): void
    {
        // A filled honeypot means a bot: answer as if it worked, save nothing.
        if (trim($this->website) !== '') {
            $this->summary = [];
            $this->sent = true;

            return;
        }

        $data = [
            'company' => trim($this->company),
            'contact_name' => trim($this->contact_name),
            'phone' => self::cleanPhone($this->phone),
            'form_region' => $this->form_region,
            'type' => $this->type,
            'message' => trim($this->message),
        ];

        $validator = Validator::make($data, [
            'company' => ['required', 'string', 'min:2', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255', 'regex:'.self::NAME_PATTERN, function ($attribute, $value, $fail) {
                if (preg_match_all('/\p{L}/u', (string) $value) < 2) {
                    $fail(ui('err.name'));
                }
            }],
            'phone' => ['required', 'regex:/^\+998\d{9}$/'],
            'form_region' => ['nullable', 'string', 'exists:regions,slug'],
            'type' => ['required', 'in:'.implode(',', Partner::KINDS)],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'company.*' => ui('err.company'),
            'contact_name.*' => ui('err.name'),
            'phone.*' => ui('err.phone'),
            'form_region.*' => ui('pf.err.choice'),
            'type.*' => ui('pf.err.choice'),
            'message.*' => ui('pf.err.long'),
        ]);

        $this->resetErrorBag();

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->focusOn('#partner-form [aria-invalid="true"]');

            return;
        }

        $limiterKey = 'partner-request:'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, self::REQUESTS_PER_HOUR)) {
            $this->addError('form', ui('pf.err.throttle'));
            $this->focusOn('#pf-form-error');

            return;
        }
        RateLimiter::hit($limiterKey, 3600);

        $region = $data['form_region'] !== '' ? Region::where('slug', $data['form_region'])->first() : null;

        PartnerRequest::create([
            'company' => $data['company'],
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'],
            'region_id' => $region?->id,
            'type' => $data['type'],
            'message' => $data['message'] !== '' ? $data['message'] : null,
            'status' => 'new',
        ]);

        $this->summary = [
            'company' => $data['company'],
            'type' => $data['type'],
            'region' => $region?->slug,
        ];
        $this->sent = true;
        $this->reset('company', 'contact_name', 'phone', 'form_region', 'type', 'message', 'website');
        $this->focusOn('#pf-done');
    }

    public function again(): void
    {
        $this->sent = false;
        $this->summary = [];
        $this->resetErrorBag();
    }

    /** Move keyboard focus once the DOM has been updated (first invalid field, confirmation). */
    protected function focusOn(string $selector): void
    {
        $this->js('setTimeout(() => document.querySelector('.json_encode($selector).')?.focus(), 60)');
    }

    public function render(): View
    {
        $regions = Region::orderBy('sort')->get();
        $current = $this->region !== '' ? $regions->firstWhere('slug', $this->region) : null;

        $all = Partner::published()
            ->where('kind', $this->tab)
            ->with(['region', 'city'])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return view('livewire.pages.dealers', [
            'regions' => $regions,
            'current' => $current,
            'counts' => $all->countBy('region_id'),
            'partners' => $current ? $all->where('region_id', $current->id)->values() : $all,
            'summaryRegion' => ($this->summary['region'] ?? null) ? $regions->firstWhere('slug', $this->summary['region']) : null,
        ])->layout('components.layouts.site', ['title' => ui('dl.h')]);
    }
}
