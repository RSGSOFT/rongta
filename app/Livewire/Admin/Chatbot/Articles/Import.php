<?php

namespace App\Livewire\Admin\Chatbot\Articles;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\KnowledgeArticle;
use App\Models\ScaleModel;
use App\Services\Chatbot\ManualDrafter;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * /admin/chatbot/articles/import: "teach from a manual". Staff upload a PDF, Claude drafts
 * articles from it, and the drafts wait unpublished for review.
 */
class Import extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation, WithFileUploads;

    /** Livewire's own temporary-upload limit is 12 MB. */
    public const MAX_KB = 12 * 1024;

    public $pdf = null;

    public string $modelId = '';

    /** Ids of the drafts made by the last run. */
    public array $draftIds = [];

    public ?string $failure = null;

    protected function rules(): array
    {
        return [
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:'.self::MAX_KB],
            'modelId' => ['nullable', 'integer', Rule::exists('scale_models', 'id')],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['pdf' => 'Руководство (PDF)', 'modelId' => 'Модель'];
    }

    public function draft(ManualDrafter $drafter): void
    {
        $this->failure = null;
        $this->draftIds = [];
        $this->validate();

        @set_time_limit(660);

        try {
            $drafts = $drafter->draft(
                $this->pdf->getRealPath(),
                $this->pdf->getClientOriginalName(),
                $this->modelId !== '' ? ScaleModel::find($this->modelId) : null,
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            $this->failure = $e->getMessage();

            return;
        }

        $this->draftIds = $drafts->pluck('id')->all();
        $this->logActivity('created', $drafts->first(), [
            'drafted_from_pdf' => $this->pdf->getClientOriginalName(),
            'articles' => $drafts->count(),
        ]);
        $this->reset('pdf');
    }

    public function render(): View
    {
        return view('livewire.admin.chatbot.articles-import', [
            'available' => app(ManualDrafter::class)->available(),
            'models' => ScaleModel::orderBy('sort')->pluck('name', 'id'),
            'drafts' => $this->draftIds ? KnowledgeArticle::whereIn('id', $this->draftIds)->orderBy('id')->get() : collect(),
            'maxMb' => self::MAX_KB / 1024,
        ])->layout('components.layouts.admin', ['title' => 'Обучение по руководству']);
    }
}
