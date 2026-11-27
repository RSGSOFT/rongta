<?php

namespace Tests\Feature\SiteB;

use App\Livewire\Pages\Knowledge;
use App\Models\Family;
use App\Models\ScaleModel;
use App\Models\ServiceCase;
use Database\Seeders\ServiceCaseSeeder;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Knowledge base service list: service fees in soʻm, the owner-approved exception to "no prices" (2026-11-24). */
class ServiceListTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(SiteBStringSeeder::class);
    }

    private function job(string $title, array $attributes = []): ServiceCase
    {
        return ServiceCase::factory()->create($attributes + [
            'title' => ['ru' => $title.' ru', 'uz' => $title.' uz', 'en' => $title],
        ]);
    }

    public function test_starter_jobs_are_loaded_for_every_group_without_amounts(): void
    {
        $expected = array_sum(array_map('count', ServiceCaseSeeder::ITEMS));

        $this->assertSame($expected, ServiceCase::count());
        $this->assertSame(0, ServiceCase::whereNotNull('amount')->count());
        $this->assertSame(count(ServiceCaseSeeder::ITEMS['label']), ServiceCase::where('family_id', Family::where('slug', 'label')->value('id'))->count());
        $this->assertSame(count(ServiceCaseSeeder::ITEMS['']), ServiceCase::whereNull('family_id')->count());

        foreach (ServiceCase::all() as $case) {
            foreach (['ru', 'uz', 'en'] as $locale) {
                $this->assertNotSame('', $case->tr('title', $locale));
            }
        }
    }

    public function test_seeder_does_not_bring_back_jobs_staff_deleted(): void
    {
        ServiceCase::query()->where('sort', '>', 1)->delete();

        $this->seed(ServiceCaseSeeder::class);

        $this->assertSame(1, ServiceCase::count());
    }

    public function test_section_is_hidden_until_a_job_has_an_amount(): void
    {
        $this->get('/en/knowledge')
            ->assertOk()
            ->assertDontSee('id="kb-service"', false)
            ->assertDontSee('href="#kb-service"', false)
            ->assertDontSee('UZS');
    }

    public function test_only_published_jobs_with_an_amount_are_listed(): void
    {
        ServiceCase::query()->delete();
        $shown = $this->job('Print head replacement', ['amount' => 450000]);
        $this->job('No amount yet', ['amount' => null]);
        $this->job('Hidden job', ['is_published' => false]);

        $this->get('/en/knowledge')
            ->assertOk()
            ->assertSee('id="kb-service"', false)
            ->assertSee('href="#kb-service"', false)
            ->assertSee('id="svc-'.$shown->id.'"', false)
            ->assertSee("450\u{00A0}000 UZS")
            ->assertSee(ui('svc.all', [], 'en'))
            ->assertSee(ui('svc.updated', ['date' => $shown->updated_at->format('d.m.Y')], 'en'))
            ->assertDontSee('No amount yet')
            ->assertDontSee('Hidden job');
    }

    public function test_amount_is_written_in_each_language_with_the_from_form(): void
    {
        ServiceCase::query()->delete();
        $this->job('Calibration', ['amount' => 120000, 'is_from' => true, 'note' => ['ru' => 'С гирями', 'uz' => 'Toshlar bilan', 'en' => 'With test weights']]);

        $this->get('/knowledge')->assertSee('Calibration ru')->assertSee("от 120\u{00A0}000 сум")->assertSee('С гирями');
        $this->get('/uz/knowledge')->assertSee('Calibration uz')->assertSee("120\u{00A0}000 soʻmdan")->assertSee('Toshlar bilan');
        $this->get('/en/knowledge')->assertSee("from 120\u{00A0}000 UZS")->assertSee('With test weights')->assertDontSee('[svc.', false);
    }

    public function test_jobs_are_grouped_by_family_and_follow_the_model_picker(): void
    {
        ServiceCase::query()->delete();
        $label = Family::where('slug', 'label')->firstOrFail();
        $comm = Family::where('slug', 'comm')->firstOrFail();
        $this->job('Diagnostics');
        $this->job('Gap sensor cleaning', ['family_id' => $label->id]);
        $this->job('Serial cable replacement', ['family_id' => $comm->id]);

        $html = $this->get('/en/knowledge')->getContent();
        $this->assertLessThan(strpos($html, 'Gap sensor cleaning'), strpos($html, 'Diagnostics'));
        $this->assertStringContainsString('<caption>'.$label->tr('name', 'en').'</caption>', $html);

        app()->setLocale('en');
        Livewire::test(Knowledge::class)
            ->set('model', ScaleModel::published()->where('family_id', $label->id)->value('slug'))
            ->assertSee('Diagnostics')
            ->assertSee('Gap sensor cleaning')
            ->assertDontSee('Serial cable replacement')
            ->set('model', '')
            ->set('q', 'serial cable')
            ->assertSee('Serial cable replacement')
            ->assertDontSee('Gap sensor cleaning');
    }
}
