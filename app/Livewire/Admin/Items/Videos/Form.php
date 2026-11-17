<?php

namespace App\Livewire\Admin\Items\Videos;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Family;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/videos/create) and edit (/admin/videos/{video}/edit). */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $videoId = null;

    public array $form = [];

    public function mount(?Video $video = null): void
    {
        $video = $video?->exists ? $video : null;
        $this->videoId = $video?->id;

        $this->form = [
            'slug' => $video->slug ?? '',
            'family_id' => $video->family_id ?? '',
            'title' => $this->transFill($video?->title),
            'url' => $video->url ?? '',
            // No link yet = placeholder by default.
            'is_placeholder' => (bool) ($video->is_placeholder ?? true),
            'sort' => $video->sort ?? ((int) Video::max('sort') + 1),
        ];
    }

    protected function rules(): array
    {
        return [
            'form.slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('videos', 'slug')->ignore($this->videoId)],
            'form.family_id' => ['nullable', 'integer', Rule::exists('families', 'id')],
            ...$this->transRules('form.title', max: 200),
            // Only web links: no javascript: or data: addresses can reach the public page.
            'form.url' => ['nullable', 'url:http,https', 'max:255'],
            'form.is_placeholder' => ['boolean'],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.slug' => 'Код',
            'form.family_id' => 'Семейство',
            'form.url' => 'Ссылка на видео',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.title', 'Название'),
        ];
    }

    public function save()
    {
        $this->validate();

        $video = $this->videoId ? Video::findOrFail($this->videoId) : new Video;
        $video->fill([
            'slug' => trim((string) $this->form['slug']) ?: null,
            'family_id' => $this->form['family_id'] ?: null,
            'title' => $this->transClean($this->form['title']),
            'url' => trim((string) $this->form['url']) ?: null,
            'is_placeholder' => (bool) $this->form['is_placeholder'],
            'sort' => (int) $this->form['sort'],
        ])->save();

        $this->logSaved($video);
        session()->flash('status', 'Видео сохранено.');

        return redirect()->route('admin.videos.index');
    }

    public function render(): View
    {
        $title = $this->videoId ? 'Видео' : 'Новое видео';

        return view('livewire.admin.items.videos-form', [
            'title' => $title,
            'families' => Family::orderBy('sort')->get()->mapWithKeys(fn ($f) => [$f->id => $f->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
