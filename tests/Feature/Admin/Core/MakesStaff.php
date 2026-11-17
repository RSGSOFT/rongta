<?php

namespace Tests\Feature\Admin\Core;

use App\Models\Family;
use App\Models\ScaleModel;
use App\Models\User;

/** Shared builders for admin panel tests. */
trait MakesStaff
{
    protected function admin(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => 'admin', 'is_active' => true]);
    }

    protected function editor(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => 'editor', 'is_active' => true]);
    }

    protected function tr(string $text): array
    {
        return ['ru' => $text, 'uz' => $text.' uz', 'en' => $text.' en'];
    }

    protected function family(array $attributes = []): Family
    {
        return Family::create($attributes + [
            'slug' => 'label',
            'name' => $this->tr('Label scales'),
            'line' => $this->tr('Line'),
            'pic' => 'pole',
            'sort' => 1,
        ]);
    }

    protected function scaleModel(?Family $family = null, array $attributes = []): ScaleModel
    {
        return ScaleModel::create($attributes + [
            'slug' => 'c1',
            'family_id' => ($family ?? $this->family())->id,
            'name' => 'C1',
            'kind' => 'comm',
            'illustration' => ['head' => null, 'cam' => false, 'receipt' => false, 'dual' => false],
            'label_print' => 'no',
            'conn' => ['rs232' => 'std'],
            'type' => $this->tr('Type'),
            'headline' => $this->tr('Headline'),
            'intro' => $this->tr('Intro'),
            'sort' => 1,
            'is_published' => true,
        ]);
    }
}
