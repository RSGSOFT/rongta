<?php

namespace App\Livewire\Admin\Partners;

/** Fixed option lists for the regions, partners and inbox screens (Russian admin labels). */
class Options
{
    public const KINDS = [
        'reseller' => 'Реселлер',
        'integrator' => 'Интегратор ПО',
    ];

    /** partners.offers values; the public site prints ui('offer.'.$key). */
    public const OFFERS = [
        'sales' => 'Продажа',
        'install' => 'Установка',
        'service' => 'Сервис',
        'integration' => 'Интеграция',
        'support' => 'Поддержка',
    ];

    public const REQUEST_STATUSES = [
        'new' => 'Новая',
        'in_progress' => 'В работе',
        'done' => 'Обработана',
        'rejected' => 'Отклонена',
    ];

    public const REQUEST_TONES = [
        'new' => 'warn',
        'in_progress' => 'accent',
        'done' => 'ok',
        'rejected' => 'neutral',
    ];

    public const LOCALES = ['ru' => 'Русский', 'uz' => 'Oʻzbekcha', 'en' => 'English'];

    /** Size of the schematic region map on the Dealers page (8 columns in the site stylesheet). */
    public const MAP_COLS = 8;

    public const MAP_ROWS = 6;

    public const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public const PHONE_REGEX = '/^\+?[0-9][0-9 ()\-]{6,24}$/';

    public const TELEGRAM_REGEX = '/^@?[A-Za-z][A-Za-z0-9_]{3,31}$/';
}
