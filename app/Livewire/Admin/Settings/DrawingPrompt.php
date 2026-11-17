<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\Setting;
use App\Support\LineArt;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * /admin/settings/lineart: the scale line-art prompt, admins only. Used by "Generate image" on
 * the model screen and shown there for copying into other AI tools. Stored in the
 * "lineart_prompt" setting; an empty setting means the bundled resources/prompts file.
 */
class DrawingPrompt extends Component
{
    use LogsActivity, RequiresStaff;

    protected bool $adminOnly = true;

    public string $prompt = '';

    public function mount(): void
    {
        $this->prompt = LineArt::prompt();
    }

    public function save(): void
    {
        $this->prompt = trim($this->prompt);
        $this->validate(['prompt' => ['required', 'string', 'min:200', 'max:40000']], [], ['prompt' => 'Промпт']);

        // The bundled text is kept as "no setting", so later updates of the file still apply.
        $value = $this->prompt === trim(LineArt::defaultPrompt()) ? null : $this->prompt;
        $row = Setting::firstOrNew(['key' => LineArt::PROMPT_SETTING]);
        $changed = $row->value !== $value;
        if ($changed) {
            $row->value = $value;
            $row->save();
            $this->logActivity('updated', $row, ['key' => LineArt::PROMPT_SETTING]);
        }
        Setting::flush();

        session()->flash('status', $changed ? 'Промпт сохранён.' : 'Изменений нет.');
    }

    public function restoreDefault(): void
    {
        $this->prompt = LineArt::defaultPrompt();
        $this->save();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.drawing-prompt', [
            'isDefault' => trim($this->prompt) === trim(LineArt::defaultPrompt()),
        ])->layout('components.layouts.admin', ['title' => 'Настройки сайта: промпт рисунков']);
    }
}
