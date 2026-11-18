<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Models\Form;
use App\Models\ActivityLog;
use App\Models\ScaleModel;
use Database\Seeders\SiteStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\Feature\Chatbot\FakeClaude;
use Tests\TestCase;

/** Photo descriptions (alt texts) of models: written by staff or by Claude, shown on the site. */
class AltTextTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    /** A 1x1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(SiteStringSeeder::class);
        $this->actingAs($this->editor());
    }

    private function modelWithPhotos(int $count, array $attributes = []): ScaleModel
    {
        $paths = [];
        for ($i = 1; $i <= $count; $i++) {
            Storage::disk('public')->put($paths[] = "models/photo-{$i}.png", base64_decode(self::PNG));
        }

        return $this->scaleModel(attributes: $attributes + [
            'type' => ['ru' => 'Весы с печатью этикеток', 'uz' => 'Yorliq chop etuvchi tarozi', 'en' => 'Label printing scale'],
            'image' => array_shift($paths),
            'gallery' => $paths ?: null,
        ]);
    }

    private static function alt(string $text): array
    {
        return ['ru' => $text, 'uz' => "{$text} o'zbekcha", 'en' => "{$text} en"];
    }

    public function test_claude_describes_photos_without_a_description_and_the_site_shows_them(): void
    {
        $model = $this->modelWithPhotos(3, ['image_alts' => ['models/photo-1.png' => self::alt('Rongta C1 спереди')]]);
        $fake = FakeClaude::bind()->text(json_encode(['photos' => [self::alt('Rongta C1 сбоку'), self::alt('Rongta C1 сзади')]]));

        $key = fn (string $path) => 'alts.'.md5($path);
        Livewire::test(Form::class, ['model' => $model])
            ->assertSet($key('models/photo-1.png').'.ru', 'Rongta C1 спереди')
            ->call('generateAlts')
            ->assertSet('altProblem', null)
            ->assertSet($key('models/photo-2.png').'.ru', 'Rongta C1 сбоку')
            ->assertSet($key('models/photo-3.png').'.uz', 'Rongta C1 сзади oʻzbekcha')
            ->set($key('models/photo-3.png'), ['ru' => '', 'uz' => '', 'en' => ''])
            ->call('save')
            ->assertHasNoErrors();

        // Only the two photos without a description were sent, in order.
        $images = array_filter($fake->requests[0]['messages'][0]['content'], fn (array $block) => $block['type'] === 'image');
        $this->assertCount(2, $images);
        $this->assertSame(1, ActivityLog::where('action', 'alt_generated')->count());

        $model->refresh();
        $this->assertSame(['models/photo-1.png', 'models/photo-2.png'], array_keys($model->image_alts));

        $this->get('/en/model/c1')->assertOk()
            ->assertSee('alt="Rongta C1 спереди en"', false)
            ->assertSee('alt="Rongta C1 сбоку en"', false)
            ->assertSee('alt="Rongta C1: Label printing scale. Photo 3"', false);
    }

    public function test_description_of_a_removed_photo_is_dropped(): void
    {
        $model = $this->modelWithPhotos(2, ['image_alts' => ['models/photo-2.png' => self::alt('Сбоку')]]);

        Livewire::test(Form::class, ['model' => $model])
            ->call('removeGalleryImage', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($model->fresh()->image_alts);
    }

    public function test_nothing_to_describe_is_explained(): void
    {
        FakeClaude::bind();
        Livewire::test(Form::class, ['model' => $this->scaleModel()])
            ->call('generateAlts')
            ->assertSet('altProblem', 'Сначала загрузите фото и сохраните модель.');
    }

    public function test_own_drawing_is_labelled_as_a_drawing_of_the_model(): void
    {
        $this->scaleModel(attributes: ['drawing' => '<rect class="i-body" x="0" y="0" width="10" height="10"/>', 'drawing_views' => ['viewBox' => '0 0 240 210']]);

        $this->get('/en/catalog')->assertOk()->assertSee('aria-label="Line drawing of the Rongta C1 scale"', false);
    }
}
