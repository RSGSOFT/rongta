<?php

namespace App\Livewire\Admin\Activity;

use App\Models\ActivityLog;
use Illuminate\Support\Str;

/** Russian labels for activity log actions and subjects (dashboard and the log viewer). */
class Labels
{
    public const ACTIONS = [
        'created' => 'Создание',
        'updated' => 'Изменение',
        'deleted' => 'Удаление',
        'login' => 'Вход',
        'logout' => 'Выход',
        'published' => 'Публикация',
        'unpublished' => 'Снятие с публикации',
        'uploaded' => 'Загрузка файла',
        'file_removed' => 'Удаление файла',
        'activated' => 'Включение доступа',
        'deactivated' => 'Отключение доступа',
        'status_changed' => 'Смена статуса',
        'exported' => 'Выгрузка',
    ];

    public const SUBJECTS = [
        'App\Models\Family' => 'Семейство',
        'App\Models\ScaleModel' => 'Модель весов',
        'App\Models\ModelFeature' => 'Преимущество модели',
        'App\Models\ModelSpec' => 'Характеристика',
        'App\Models\Document' => 'Документ',
        'App\Models\Video' => 'Видео',
        'App\Models\User' => 'Сотрудник',
        'App\Models\Region' => 'Регион',
        'App\Models\City' => 'Город',
        'App\Models\Partner' => 'Партнёр',
        'App\Models\PartnerUser' => 'Доступ партнёра',
        'App\Models\PartnerRequest' => 'Заявка на партнёрство',
        'App\Models\Lead' => 'Лид',
        'App\Models\UiString' => 'Текст интерфейса',
        'App\Models\FaqTopic' => 'Тема вопросов',
        'App\Models\FaqItem' => 'Вопрос',
        'App\Models\GuideEntry' => 'Гид по выбору',
        'App\Models\GuideCheck' => 'Пункт гида',
        'App\Models\ChooserOption' => 'Вариант подбора',
        'App\Models\Setting' => 'Настройка',
    ];

    public static function action(string $action): string
    {
        return self::ACTIONS[$action] ?? $action;
    }

    public static function subject(?string $type): string
    {
        if (! $type) {
            return '';
        }

        return self::SUBJECTS[$type] ?? Str::headline(class_basename($type));
    }

    /** "Модель весов #12" */
    public static function describe(ActivityLog $log): string
    {
        $subject = self::subject($log->subject_type);

        return $subject === '' ? '' : $subject.($log->subject_id ? ' #'.$log->subject_id : '');
    }
}
