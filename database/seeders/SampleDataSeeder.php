<?php

namespace Database\Seeders;

use App\Models\ChooserOption;
use App\Models\City;
use App\Models\Document;
use App\Models\Family;
use App\Models\FaqItem;
use App\Models\FaqTopic;
use App\Models\GuideCheck;
use App\Models\GuideEntry;
use App\Models\Region;
use App\Models\ScaleModel;
use App\Models\Video;
use Carbon\Carbon;
use Database\Seeders\Concerns\ReadsDesignData;
use Illuminate\Database\Seeder;

/**
 * sample-data.json. Regions, FAQ, buying guide and chooser are real content.
 * Documents and videos are placeholders (is_placeholder = true). The sample dealers and
 * integrators are not imported (owner decision): only their regions link cities to regions.
 * The file carries no buying guide "cost" row and no partner terms (prices, the 10 + 1 offer).
 */
class SampleDataSeeder extends Seeder
{
    use ReadsDesignData;

    public function run(): void
    {
        $data = $this->designData('sample-data.json');

        $families = Family::pluck('id', 'slug');
        $models = ScaleModel::pluck('id', 'slug');

        // Regions
        $regions = [];
        foreach ($data['regions'] as $i => $region) {
            $regions[$region['id']] = Region::updateOrCreate(
                ['slug' => $region['id']],
                [
                    'name' => $region['name'],
                    'short' => $region['short'],
                    'map_col' => $region['pos'][0],
                    'map_row' => $region['pos'][1],
                    'sort' => $i + 1,
                ],
            )->id;
        }

        // Cities. The file does not link a city to a region; the link is taken from the
        // sample dealers that use the city, and stays null where none does.
        $cityRegion = [];
        foreach (array_merge($data['resellers'], $data['integrators']) as $partner) {
            $cityRegion[$partner['city']['en']] ??= $partner['region'];
        }
        $cities = [];
        $n = 0;
        foreach ($data['cities'] as $slug => $name) {
            $regionSlug = $cityRegion[$name['en']] ?? null;
            $cities[$name['en']] = City::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'region_id' => $regionSlug ? ($regions[$regionSlug] ?? null) : null, 'sort' => ++$n],
            )->id;
        }

        // Documents (placeholders: version, date and size are sample values, no file)
        foreach ($data['documents'] as $i => $doc) {
            if (! isset($models[$doc['model']])) {
                continue;
            }
            Document::updateOrCreate(
                ['slug' => $doc['id']],
                [
                    'scale_model_id' => $models[$doc['model']],
                    'type' => $doc['type'],
                    'title' => $data['documentTypes'][$doc['type']]['title'] ?? null,
                    'format' => $doc['fmt'] ?? ($data['documentTypes'][$doc['type']]['fmt'] ?? null),
                    'version' => $doc['ver'] ?? null,
                    'released_on' => isset($doc['date']) ? Carbon::createFromFormat('d.m.Y', $doc['date'])->startOfDay() : null,
                    'size_label' => $doc['size'] ?? null,
                    'is_placeholder' => true,
                    'sort' => $i + 1,
                ],
            );
        }

        // Videos (placeholders)
        foreach ($data['videos'] as $i => $video) {
            Video::updateOrCreate(
                ['slug' => $video['id']],
                [
                    'family_id' => $families[$video['fam']] ?? null,
                    'title' => $video['title'],
                    'is_placeholder' => true,
                    'sort' => $i + 1,
                ],
            );
        }

        // FAQ
        foreach ($data['faq'] as $i => $topic) {
            $faqTopic = FaqTopic::updateOrCreate(
                ['slug' => $topic['id']],
                ['name' => $topic['topic'], 'sort' => $i + 1],
            );
            foreach ($topic['items'] as $j => $item) {
                FaqItem::updateOrCreate(
                    ['faq_topic_id' => $faqTopic->id, 'sort' => $j + 1],
                    ['question' => $item['q'], 'answer' => $item['a']],
                );
            }
        }

        // Buying guide
        foreach ($data['buyingGuide'] as $i => $entry) {
            if (! isset($families[$entry['fam']])) {
                continue;
            }
            GuideEntry::updateOrCreate(
                ['family_id' => $families[$entry['fam']]],
                [
                    'does' => $entry['does'],
                    'fits' => $entry['fits'],
                    'limit' => $entry['limit'],
                    'peak' => $entry['peak'],
                    'sort' => $i + 1,
                ],
            );
        }
        foreach ($data['buyingGuideChecklist'] as $i => $text) {
            GuideCheck::updateOrCreate(['sort' => $i + 1], ['text' => $text]);
        }

        // Chooser
        $n = 0;
        foreach ($data['chooser'] as $key => $option) {
            $chooser = ChooserOption::updateOrCreate(
                ['key' => $key],
                ['family_id' => $families[$option['fam']] ?? null, 'why' => $option['why'], 'sort' => ++$n],
            );
            $sync = [];
            foreach ($option['models'] as $j => $slug) {
                if (isset($models[$slug])) {
                    $sync[$models[$slug]] = ['note' => $option['notes'][$slug] ?? null, 'sort' => $j + 1];
                }
            }
            $chooser->scaleModels()->sync($sync);
        }
    }
}
