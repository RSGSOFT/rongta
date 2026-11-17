<?php

namespace App\Livewire\Admin\Leads;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\Lead;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** /admin/leads: contacts left at the chatbot gate, newest first, with a CSV export of the current filter. */
class Index extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation, WithTable;

    protected array $searchable = ['first_name', 'last_name', 'phone'];

    protected array $sortable = ['id', 'last_name', 'created_at'];

    protected string $defaultSort = 'id';

    protected string $defaultDir = 'desc';

    #[Url(as: 'lang', except: '')]
    public string $filterLocale = '';

    #[Url(as: 'from', except: '')]
    public string $filterFrom = '';

    #[Url(as: 'to', except: '')]
    public string $filterTo = '';

    protected function validDate(string $value): ?string
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    protected function filtered(): Builder
    {
        $from = $this->validDate($this->filterFrom);
        $to = $this->validDate($this->filterTo);

        $query = Lead::query()
            ->when($this->filterValue($this->filterLocale), fn (Builder $q, $locale) => $q->where('locale', $locale))
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from.' 00:00:00'))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to.' 23:59:59'));

        return $this->applySearch($query);
    }

    /** A cell that a spreadsheet would run as a formula is neutralised with a leading apostrophe. */
    public static function csvCell(mixed $value): string
    {
        $text = trim(str_replace(["\r\n", "\r", "\n", "\t"], ' ', (string) $value));

        // A plain international phone number ("+998901234567") is safe and stays as it is.
        if (preg_match('/^\+\d{7,15}$/', $text)) {
            return $text;
        }

        return preg_match('/^[=+\-@]/', $text) ? "'".$text : $text;
    }

    /** CSV for Excel: UTF-8 with BOM, semicolon separated, the rows of the current search and filters. */
    public function export(): StreamedResponse
    {
        $query = $this->filtered()->withCount('conversations')->orderBy('id');
        $count = (clone $query)->count();

        $this->logActivity('exported', null, ['what' => 'leads', 'rows' => $count]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Дата', 'Имя', 'Фамилия', 'Телефон', 'Язык', 'Источник', 'Страница', 'Диалогов'], ';', '"', '');

            $query->chunkById(500, function ($leads) use ($out) {
                foreach ($leads as $lead) {
                    fputcsv($out, array_map([self::class, 'csvCell'], [
                        $lead->id,
                        $lead->created_at?->format('d.m.Y H:i'),
                        $lead->first_name,
                        $lead->last_name,
                        $lead->phone,
                        $lead->locale,
                        $lead->source,
                        $lead->page_url,
                        $lead->conversations_count,
                    ]), ';', '"', '');
                }
            });

            fclose($out);
        }, 'rongta-leads-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        $rows = $this->applySort($this->filtered()->withCount('conversations'))->paginate($this->perPage);

        return view('livewire.admin.leads.index', [
            'rows' => $rows,
            'total' => Lead::count(),
        ])->layout('components.layouts.admin', ['title' => 'Лиды']);
    }
}
