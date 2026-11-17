<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Models\Drawing;
use App\Livewire\Admin\Settings\DrawingPrompt;
use App\Models\ActivityLog;
use App\Models\ScaleModel;
use App\Models\Setting;
use App\Services\Catalog\LineArtGenerator;
use App\Support\LineArt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\Feature\Chatbot\FakeClaude;
use Tests\TestCase;

/** Line drawings of models: cleaning, AI generation from photos, upload, and use on the site. */
class LineArtTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    /** A 1x1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private const DRAWING = '<svg viewBox="0 0 240 210"><ellipse class="i-shadow" cx="120" cy="184" rx="90" ry="6"/>'
        .'<rect class="i-body" x="32" y="138" width="176" height="36" rx="9"/><text class="i-led" x="100" y="150" font-size="12">1.250</text></svg>';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs($this->editor());
    }

    private function modelWithPhotos(int $count = 2): ScaleModel
    {
        $paths = [];
        for ($i = 1; $i <= $count; $i++) {
            Storage::disk('public')->put($paths[] = "models/photo-{$i}.png", base64_decode(self::PNG));
        }

        return $this->scaleModel(attributes: ['image' => array_shift($paths), 'gallery' => $paths ?: null]);
    }

    private function reply(array $overrides = []): string
    {
        return json_encode([
            'parts' => ['Корпус: широкий, углы скруглены', 'Клавиатура: 4 ряда по 8 клавиш'],
            'svg' => self::DRAWING,
            'crops' => [['viewBox' => '20 108 144 120', 'key' => 'view.display'], ['viewBox' => '0 0 0 0', 'key' => 'view.platter']],
            'note' => 'Кабель не виден на фото и не нарисован.',
            'problem' => '',
            ...$overrides,
        ]);
    }

    public function test_clean_keeps_shapes_and_classes_and_drops_everything_else(): void
    {
        $dirty = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 210" onload="alert(1)">'
            .'<script>alert(1)</script><style>.i-body{fill:red}</style>'
            .'<rect class="i-body evil" x="1" y="2" width="3" height="4" fill="red" style="x" onclick="alert(1)"/>'
            .'<a href="javascript:alert(1)"><circle cx="1" cy="1" r="1"/></a>'
            .'<image href="http://x/y.png"/><foreignObject><div>hi</div></foreignObject>'
            .'<path class="i-line" d="M0 0L10 10" transform="url(javascript:1)"/>'
            .'<text class="i-led" x="5" y="5">1.250<tspan>x</tspan></text></svg>';

        $clean = LineArt::clean($dirty);

        $this->assertSame('0 0 240 210', $clean['viewBox']);
        $this->assertSame('<rect x="1" y="2" width="3" height="4" class="i-body"/><path d="M0 0L10 10" class="i-line"/><text x="5" y="5" class="i-led">1.250<tspan>x</tspan></text>', $clean['markup']);
        foreach (['script', 'style', 'onload', 'onclick', 'href', 'image', 'foreignObject', 'fill=', 'evil', 'javascript'] as $bad) {
            $this->assertStringNotContainsString($bad, $clean['markup']);
        }
    }

    public function test_clean_refuses_entities_and_empty_or_non_svg_input(): void
    {
        $this->assertNull(LineArt::clean('<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg><text>&x;</text></svg>'));
        $this->assertNull(LineArt::clean('<svg viewBox="0 0 10 10"><script>1</script></svg>'));
        $this->assertNull(LineArt::clean('not svg at all <'));
        $this->assertNull(LineArt::clean(''));
        $this->assertSame('<rect x="1" y="1" width="2" height="2" class="i-body"/>', LineArt::clean('<rect class="i-body" x="1" y="1" width="2" height="2"/>')['markup']);
    }

    public function test_generate_sends_the_prompt_and_photos_and_shows_a_clean_draft(): void
    {
        $model = $this->modelWithPhotos(2);
        $claude = FakeClaude::bind()->text($this->reply(['svg' => str_replace('</svg>', '<script>alert(1)</script></svg>', self::DRAWING)]));

        $component = Livewire::test(Drawing::class, ['modelId' => $model->id])
            ->assertSet('selected', ['models/photo-1.png', 'models/photo-2.png'])
            ->call('generate')
            ->assertSet('problem', null)
            ->assertSee('Клавиатура: 4 ряда по 8 клавиш')
            ->assertSee('Использовать');

        $request = $claude->requests[0];
        $this->assertStringContainsString('# Scale line art', $request['system']);
        $this->assertStringContainsString('How you are called here', $request['system']);
        $content = $request['messages'][0]['content'];
        $this->assertSame(['image', 'image', 'text'], array_column($content, 'type'));
        $this->assertSame('image/png', $content[0]['source']['mediaType']);
        $this->assertStringContainsString($model->name, $content[2]['text']);

        $draft = $component->get('draft');
        $this->assertStringNotContainsString('script', $draft['markup']);
        $this->assertSame([['viewBox' => '20 108 144 120', 'key' => 'view.display']], $draft['crops']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'drawing_generated', 'subject_id' => $model->id]);
        $this->assertNull($model->fresh()->drawing, 'A draft is not stored before "Use".');

        $component->call('useDraft')->assertSet('draft', null)->assertSee('Рисунок сохранён');
        $model->refresh();
        $this->assertStringContainsString('class="i-body"', $model->drawing);
        $this->assertStringNotContainsString('script', $model->drawing);
        $this->assertSame(['viewBox' => '0 0 240 210', 'crops' => [['viewBox' => '20 108 144 120', 'key' => 'view.display']]], $model->drawing_views);
    }

    public function test_only_the_models_own_photos_are_sent(): void
    {
        $model = $this->modelWithPhotos(1);
        $claude = FakeClaude::bind();

        Livewire::test(Drawing::class, ['modelId' => $model->id])
            ->set('selected', ['../../.env', 'models/other.png'])
            ->call('generate')
            ->assertSet('problem', 'Отметьте от 1 до '.LineArtGenerator::MAX_PHOTOS.' фото модели.');

        $this->assertSame([], $claude->requests);
    }

    public function test_claude_reporting_unusable_photos_is_shown_and_nothing_is_drafted(): void
    {
        $model = $this->modelWithPhotos(1);
        FakeClaude::bind()->text($this->reply(['svg' => '', 'problem' => 'На фото не весы.']));

        Livewire::test(Drawing::class, ['modelId' => $model->id])
            ->call('generate')
            ->assertSet('draft', null)
            ->assertSet('problem', 'Claude не смог нарисовать модель: На фото не весы.');
    }

    public function test_an_svg_made_elsewhere_can_be_uploaded_and_used(): void
    {
        $model = $this->modelWithPhotos(1);
        $file = UploadedFile::fake()->createWithContent('drawing.svg', str_replace('<rect', '<rect onclick="x()"', self::DRAWING));

        Livewire::test(Drawing::class, ['modelId' => $model->id])
            ->set('svgFile', $file)
            ->assertSet('draft.source', 'file')
            ->call('useDraft');

        $this->assertStringContainsString('class="i-body"', $model->fresh()->drawing);
        $this->assertStringNotContainsString('onclick', $model->fresh()->drawing);
    }

    public function test_the_drawing_replaces_the_photo_on_the_site(): void
    {
        $model = $this->modelWithPhotos(1);
        $model->forceFill([
            'drawing' => LineArt::clean(self::DRAWING)['markup'],
            'drawing_views' => ['viewBox' => '0 0 240 210', 'crops' => [['viewBox' => '20 108 144 120', 'key' => 'view.display']]],
        ])->save();

        $html = $this->get(route('model', ['slug' => $model->slug]))->assertOk()->getContent();

        $this->assertStringContainsString('<rect x="32" y="138" rx="9" width="176" height="36" class="i-body"/>', $html);
        $this->assertStringContainsString('viewBox="20 108 144 120"', $html);
        // The photo stays in the gallery, after the drawing and its crop.
        $this->assertStringContainsString('storage/models/photo-1.png', $html);
    }

    public function test_the_prompt_is_editable_by_admins_only_and_used_for_generation(): void
    {
        $this->get(route('admin.settings.lineart'))->assertForbidden();

        $this->actingAs($this->admin());
        $custom = LineArt::defaultPrompt()."\n\nAlways draw the keyboard in grey.";
        Livewire::test(DrawingPrompt::class)->set('prompt', $custom)->call('save')->assertHasNoErrors();

        $this->assertSame(trim($custom), Setting::where('key', LineArt::PROMPT_SETTING)->value('value'));
        $this->assertStringContainsString('Always draw the keyboard in grey.', LineArt::hint($this->modelWithPhotos(1)));

        Livewire::test(DrawingPrompt::class)->call('restoreDefault');
        $this->assertNull(Setting::where('key', LineArt::PROMPT_SETTING)->value('value'));
        $this->assertTrue(ActivityLog::where('action', 'updated')->where('subject_type', (new Setting)->getMorphClass())->exists());
    }
}
