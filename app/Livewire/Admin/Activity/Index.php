<?php

namespace App\Livewire\Admin\Activity;

use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Read-only activity log (/admin/activity). Admins only (route middleware staff:admin). */
class Index extends Component
{
    use RequiresStaff, WithTable;

    protected bool $adminOnly = true;

    protected array $sortable = ['id'];

    protected string $defaultSort = 'id';

    protected string $defaultDir = 'desc';

    #[Url(as: 'user', except: '')]
    public string $filterUser = '';

    #[Url(as: 'action', except: '')]
    public string $filterAction = '';

    #[Url(as: 'subject', except: '')]
    public string $filterSubject = '';

    #[Url(as: 'from', except: '')]
    public string $filterFrom = '';

    #[Url(as: 'to', except: '')]
    public string $filterTo = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->perPage = 50;
    }

    public function clearFilters(): void
    {
        $this->reset('filterUser', 'filterAction', 'filterSubject', 'filterFrom', 'filterTo');
        $this->resetPage();
    }

    private function day(string $value): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        $from = $this->day($this->filterFrom);
        $to = $this->day($this->filterTo);

        $query = ActivityLog::query()
            ->with('user')
            ->when($this->filterValue($this->filterUser), fn ($q, $id) => $q->where('user_id', (int) $id))
            ->when($this->filterValue($this->filterAction), fn ($q, $action) => $q->where('action', $action))
            ->when($this->filterValue($this->filterSubject), fn ($q, $type) => $q->where('subject_type', $type))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()));

        $subjects = ActivityLog::query()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type')
            ->mapWithKeys(fn ($type) => [$type => Labels::subject($type)]);
        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action')
            ->mapWithKeys(fn ($action) => [$action => Labels::action($action)]);

        return view('livewire.admin.activity', [
            'rows' => $this->tableRows($query),
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'subjects' => $subjects,
            'actions' => $actions,
            'filtered' => $this->filterUser.$this->filterAction.$this->filterSubject.$this->filterFrom.$this->filterTo !== '',
        ])->layout('components.layouts.admin', ['title' => 'Журнал действий']);
    }
}
