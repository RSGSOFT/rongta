<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\ModelFeature;
use App\Models\ModelSpec;
use App\Models\ScaleModel;
use Database\Seeders\Concerns\ReadsDesignData;
use Illuminate\Database\Seeder;

/** Families, models, features and specs from models.json. The "price" field is never read. */
class CatalogSeeder extends Seeder
{
    use ReadsDesignData;

    public function run(): void
    {
        $data = $this->designData('models.json');

        $families = [];
        foreach ($data['families'] as $i => $family) {
            $families[$family['id']] = Family::updateOrCreate(
                ['slug' => $family['id']],
                ['name' => $family['name'], 'line' => $family['line'], 'pic' => $family['pic'] ?? null, 'sort' => $i + 1],
            );
        }

        // key => [group, sort] following specGroups order.
        $specOrder = [];
        $n = 0;
        foreach ($data['specGroups'] as $group) {
            foreach ($group['keys'] as $key) {
                $specOrder[$key] = [$group['group'], ++$n];
            }
        }

        foreach ($data['models'] as $i => $item) {
            $model = ScaleModel::updateOrCreate(
                ['slug' => $item['id']],
                [
                    'family_id' => $families[$item['fam']]->id,
                    'name' => $item['name'],
                    'kind' => $item['kind'],
                    'illustration' => [
                        'head' => $item['head'] ?? null,
                        'cam' => (bool) ($item['cam'] ?? false),
                        'receipt' => (bool) ($item['receipt'] ?? false),
                        'dual' => (bool) ($item['dual'] ?? false),
                    ],
                    'is_aurora' => (bool) ($item['aurora'] ?? false),
                    'label_print' => $item['labelPrint'],
                    'conn' => $item['conn'],
                    'type' => $item['type'],
                    'headline' => $item['headline'],
                    'intro' => $item['intro'],
                    'source_url' => $item['url'] ?? null,
                    'sort' => $i + 1,
                ],
            );

            foreach ($item['features'] as $f => $feature) {
                ModelFeature::updateOrCreate(
                    ['scale_model_id' => $model->id, 'sort' => $f + 1],
                    ['icon' => $feature['icon'] ?? null, 'title' => $feature['title'], 'text' => $feature['text']],
                );
            }

            foreach ($item['specs'] as $key => $value) {
                [$group, $sort] = $specOrder[$key] ?? ['general', 1000];
                ModelSpec::updateOrCreate(
                    ['scale_model_id' => $model->id, 'key' => $key],
                    ['group' => $group, 'value' => $value, 'sort' => $sort],
                );
            }
        }
    }
}
