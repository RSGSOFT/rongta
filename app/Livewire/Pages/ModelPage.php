<?php

namespace App\Livewire\Pages;

use App\Models\ModelSpec;
use App\Models\ScaleModel;
use App\Support\LineArt;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** Model story page: headline, gallery, features, grouped specs, downloads, similar models, where to buy. */
class ModelPage extends Component
{
    public ScaleModel $model;

    /** Detail crops of the schematic drawing used as gallery views: kind => [[viewBox, caption key], ...]. */
    public const CROPS = [
        'pole' => [['84 0 144 120', 'view.display'], ['108 96 132 110', 'view.printer']],
        'flat' => [['120 80 120 100', 'view.display'], ['6 84 156 130', 'view.platter']],
        'hang' => [['48 6 156 130', 'view.display'], ['42 84 156 130', 'view.pan']],
        'comm' => [['20 108 144 120', 'view.display'], ['84 70 144 120', 'view.platter']],
        'c3' => [['30 116 120 100', 'view.display'], ['132 116 108 90', 'view.cable']],
        'c2ai' => [['90 14 132 110', 'view.camera'], ['20 108 144 120', 'view.display']],
        'aipc' => [['56 0 180 150', 'view.screen'], ['120 110 120 100', 'view.printer']],
        'y2l' => [['84 0 156 130', 'view.screen'], ['120 84 120 100', 'view.printer']],
    ];

    public function mount(string $slug): void
    {
        $this->model = ScaleModel::published()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Gallery views. With real photos: the main image and the gallery images.
     * Until then: the schematic drawing and two detail crops of it.
     *
     * @return list<array{src: ?string, viewBox: ?string, caption: string}>
     */
    protected function views(): array
    {
        $model = $this->model;

        // The model's own line drawing and its detail crops, then its real photos.
        if (filled($model->drawing)) {
            $views = [['src' => null, 'viewBox' => $model->drawing_views['viewBox'] ?? '0 0 240 210', 'caption' => ui('view.general')]];
            foreach (LineArt::crops((array) ($model->drawing_views['crops'] ?? [])) as $crop) {
                $views[] = ['src' => null, 'viewBox' => $crop['viewBox'], 'caption' => ui($crop['key'])];
            }
            $photos = filled($model->image) ? array_merge([$model->image], array_values(array_filter((array) $model->gallery, 'is_string'))) : [];
            foreach ($photos as $i => $path) {
                $views[] = ['src' => self::fileUrl($path), 'viewBox' => null, 'caption' => ui('site.view.photo', ['n' => $i + 1])];
            }

            return $views;
        }

        if (filled($model->image)) {
            $views = [];
            foreach (array_merge([$model->image], array_values(array_filter((array) $model->gallery, 'is_string'))) as $i => $path) {
                $views[] = [
                    'src' => self::fileUrl($path),
                    'viewBox' => null,
                    'caption' => $i === 0 ? ui('view.general') : ui('site.view.photo', ['n' => $i + 1]),
                ];
            }

            return $views;
        }

        $views = [['src' => null, 'viewBox' => '0 0 240 210', 'caption' => ui('view.general')]];
        foreach (self::CROPS[$model->kind] ?? [] as [$viewBox, $key]) {
            $views[] = ['src' => null, 'viewBox' => $viewBox, 'caption' => ui($key)];
        }

        return $views;
    }

    /** Public URL for a stored image path (absolute URL, path under public/, or the public storage disk). */
    public static function fileUrl(string $path): string
    {
        if (preg_match('#^(https?:)?//#', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return is_file(public_path($path)) ? asset($path) : asset('storage/'.ltrim($path, '/'));
    }

    public function render(): View
    {
        $model = $this->model->loadMissing(['family', 'features', 'specs', 'documents.scaleModel']);

        // Specs grouped in catalog order; groups outside the known list go last.
        $byGroup = $model->specs->groupBy('group');
        $order = array_merge(ModelSpec::GROUPS, array_diff($byGroup->keys()->all(), ModelSpec::GROUPS));
        $specGroups = [];
        foreach ($order as $group) {
            if ($byGroup->has($group)) {
                $specGroups[$group] = $byGroup[$group];
            }
        }

        return view('livewire.pages.model', [
            'family' => $model->family,
            'views' => $this->views(),
            'specGroups' => $specGroups,
            'documents' => $model->documents,
            'similar' => ScaleModel::published()
                ->where('family_id', $model->family_id)
                ->whereKeyNot($model->getKey())
                ->orderBy('sort')
                ->limit(3)
                ->get(),
        ])->layout('components.layouts.site', [
            'title' => $model->seoTitle(),
            'description' => $model->seoDescription(),
            'image' => Seo::modelImages($model)[0] ?? null,
            'schema' => [
                Seo::product($model),
                Seo::breadcrumbs(array_values(array_filter([
                    [ui('nav.home'), lroute('home')],
                    [ui('nav.catalog'), lroute('catalog')],
                    $model->family ? [$model->family->tr('name'), lroute('catalog', ['family' => $model->family->slug])] : null,
                    [$model->name, lroute('model', ['slug' => $model->slug])],
                ]))),
            ],
        ]);
    }
}
