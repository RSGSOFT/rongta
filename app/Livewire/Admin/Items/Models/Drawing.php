<?php

namespace App\Livewire\Admin\Items\Models;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\ScaleModel;
use App\Services\Catalog\LineArtGenerator;
use App\Support\LineArt;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Main picture of a saved model as a line drawing, on its edit screen. Staff either let Claude
 * draw it from the model's saved photos ("Generate") or upload an SVG made elsewhere. Either way
 * the result is a draft shown on a light and a dark background; "Use" stores it on the model.
 * Every SVG passes LineArt::clean() before it is shown or stored.
 */
class Drawing extends Component
{
    use LogsActivity, RequiresStaff, WithFileUploads;

    #[Locked]
    public int $modelId;

    /** Photo paths (public disk) chosen to send to Claude. */
    public array $selected = [];

    /** The draft drawing: {markup, viewBox, crops, parts, note, source}. Only the server sets it. */
    #[Locked]
    public ?array $draft = null;

    #[Locked]
    public ?string $problem = null;

    /** Success message shown in the card (the page does not reload). */
    #[Locked]
    public ?string $status = null;

    /** An SVG file made in another AI tool (temporary upload). */
    public $svgFile = null;

    public function mount(int $modelId): void
    {
        $this->modelId = $modelId;
        $this->selected = array_slice($this->photos(), 0, LineArtGenerator::MAX_PHOTOS);
    }

    /** The model's saved photos: main photo first, then the gallery. */
    private function photos(): array
    {
        $model = ScaleModel::findOrFail($this->modelId);

        return array_values(array_unique(array_filter(
            array_merge([$model->image], (array) $model->gallery),
            fn ($path) => is_string($path) && $path !== '',
        )));
    }

    public function generate(LineArtGenerator $generator): void
    {
        $this->problem = $this->status = null;
        $chosen = array_values(array_intersect($this->photos(), $this->selected));
        if ($chosen === [] || count($chosen) > LineArtGenerator::MAX_PHOTOS) {
            $this->problem = 'Отметьте от 1 до '.LineArtGenerator::MAX_PHOTOS.' фото модели.';

            return;
        }

        // Drawing from photos takes a while; the request itself allows up to 10 minutes.
        @set_time_limit(660);

        $model = ScaleModel::findOrFail($this->modelId);
        try {
            $this->draft = [...$generator->generate($model, $chosen), 'source' => 'ai'];
        } catch (RuntimeException $e) {
            $this->draft = null;
            $this->problem = $e->getMessage();

            return;
        } finally {
            $reply = $generator->lastReply;
            $this->logActivity('drawing_generated', $model, [
                'photos' => count($chosen),
                'ok' => $this->draft !== null,
                'input_tokens' => $reply?->inputTokens,
                'output_tokens' => $reply?->outputTokens,
            ]);
        }
    }

    public function updatedSvgFile(): void
    {
        $this->problem = $this->status = null;
        $this->validate(['svgFile' => ['required', 'file', 'max:'.intdiv(LineArt::MAX_INPUT_BYTES, 1024), 'extensions:svg']], [], ['svgFile' => 'Файл SVG']);

        $drawing = LineArt::clean((string) file_get_contents($this->svgFile->getRealPath()));
        $this->svgFile = null;
        if ($drawing === null) {
            $this->problem = 'В файле нет пригодного рисунка SVG. Нужен файл .svg с фигурами, размеченными классами i-body, i-steel и другими из промпта.';

            return;
        }

        $this->draft = [...$drawing, 'crops' => [], 'parts' => [], 'note' => 'Загружено из файла.', 'source' => 'file'];
    }

    public function useDraft(): void
    {
        if ($this->draft === null) {
            return;
        }

        // Cleaned again on the way in, so only a clean drawing is ever stored.
        $drawing = LineArt::clean('<svg xmlns="http://www.w3.org/2000/svg" viewBox="'.e($this->draft['viewBox']).'">'.$this->draft['markup'].'</svg>');
        if ($drawing === null) {
            $this->problem = 'Рисунок не прошёл проверку. Сгенерируйте или загрузите его заново.';

            return;
        }

        $model = ScaleModel::findOrFail($this->modelId);
        $model->forceFill([
            'drawing' => $drawing['markup'],
            'drawing_views' => ['viewBox' => $drawing['viewBox'], 'crops' => LineArt::crops($this->draft['crops'] ?? [])],
        ])->save();
        $this->logActivity('updated', $model, ['drawing' => $this->draft['source'] ?? 'ai']);

        $this->draft = null;
        $this->status = 'Рисунок сохранён. Он показывается на сайте как главное изображение модели.';
    }

    public function discard(): void
    {
        $this->draft = null;
        $this->problem = $this->status = null;
    }

    public function removeDrawing(): void
    {
        $model = ScaleModel::findOrFail($this->modelId);
        $model->forceFill(['drawing' => null, 'drawing_views' => null])->save();
        $this->logActivity('updated', $model, ['drawing' => null]);

        $this->status = 'Рисунок убран. На сайте снова показывается фото или схема.';
    }

    public function render(LineArtGenerator $generator): View
    {
        $model = ScaleModel::findOrFail($this->modelId);
        $views = (array) ($model->drawing_views ?? []);

        return view('livewire.admin.items.models-drawing', [
            'model' => $model,
            'photos' => $this->photos(),
            'available' => $generator->available(),
            'current' => filled($model->drawing) ? [
                'light' => LineArt::dataUrl($model->drawing, $views['viewBox'] ?? LineArt::VIEWBOX),
                'night' => LineArt::dataUrl($model->drawing, $views['viewBox'] ?? LineArt::VIEWBOX, night: true),
            ] : null,
            'preview' => $this->draft ? [
                'light' => LineArt::dataUrl($this->draft['markup'], $this->draft['viewBox']),
                'night' => LineArt::dataUrl($this->draft['markup'], $this->draft['viewBox'], night: true),
                'crops' => array_map(fn (array $crop) => [
                    'src' => LineArt::dataUrl($this->draft['markup'], $crop['viewBox']),
                    'caption' => ui($crop['key'], [], 'ru'),
                ], $this->draft['crops'] ?? []),
            ] : null,
            'hint' => LineArt::hint($model),
        ]);
    }
}
