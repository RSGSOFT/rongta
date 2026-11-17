<?php

namespace Tests\Feature\Admin\Content;

use App\Models\City;
use App\Models\Partner;
use App\Models\Region;

/** Shared builders for the admin "content" tests (used next to MakesStaff). */
trait MakesContent
{
    protected function region(array $attributes = []): Region
    {
        return Region::create($attributes + [
            'slug' => 'tashkent-city',
            'name' => $this->tr('Ташкент'),
            'short' => $this->tr('Ташкент'),
            'map_col' => 6,
            'map_row' => 1,
            'sort' => 1,
        ]);
    }

    protected function city(Region $region, array $attributes = []): City
    {
        return City::create($attributes + [
            'slug' => 'chilanzar',
            'region_id' => $region->id,
            'name' => $this->tr('Чиланзар'),
            'sort' => 1,
        ]);
    }

    protected function partner(Region $region, array $attributes = []): Partner
    {
        return Partner::create($attributes + [
            'kind' => 'reseller',
            'name' => $this->tr('Торг Сервис'),
            'region_id' => $region->id,
            'offers' => ['sales'],
            'is_placeholder' => false,
            'is_published' => true,
            'sort' => 1,
        ]);
    }
}
