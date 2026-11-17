<?php

namespace App\Livewire\Admin\Dashboard;

use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\ScaleModel;
use App\Models\UiString;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/** /admin: counts that need attention, each linked to its screen when that screen exists. */
class Index extends Component
{
    use RequiresStaff;

    public function render(): View
    {
        $models = ScaleModel::count();
        $hidden = ScaleModel::where('is_published', false)->count();
        $partners = Partner::count();
        $requests = PartnerRequest::where('status', 'new')->count();
        $leads = Lead::count();
        $leadsWeek = Lead::where('created_at', '>=', now()->subDays(7))->count();
        $docsNoFile = Document::whereNull('file_path')->orWhere('file_path', '')->count();
        $strings = UiString::where(function ($q) {
            foreach (['ru', 'uz', 'en'] as $locale) {
                $q->orWhereNull($locale)->orWhere($locale, '');
            }
        })->count();

        // [label, value, note, route name, route parameters, needs attention]
        $cards = [
            ['Модели весов', $models, $hidden ? "скрыто с сайта: {$hidden}" : 'все опубликованы', 'admin.models.index', [], false],
            ['Партнёры', $partners, 'реселлеры и интеграторы', 'admin.partners.index', [], false],
            ['Новые заявки на партнёрство', $requests, 'со статусом «новая»', 'admin.partner-requests.index', [], $requests > 0],
            ['Лиды из чат-бота', $leadsWeek, "за 7 дней · всего {$leads}", 'admin.leads.index', [], $leadsWeek > 0],
            ['Документы без файла', $docsNoFile, 'на сайте нет ссылки для скачивания', 'admin.documents.index', ['file' => 'missing'], $docsNoFile > 0],
            ['Тексты без перевода', $strings, 'пусто в RU, UZ или EN', 'admin.strings.index', [], $strings > 0],
        ];

        $cards = array_map(fn (array $c) => [
            'label' => $c[0],
            'value' => $c[1],
            'note' => $c[2],
            'url' => Route::has($c[3]) ? route($c[3], $c[4]) : null,
            'attention' => $c[5],
        ], $cards);

        $user = auth()->user();

        return view('livewire.admin.dashboard', [
            'cards' => $cards,
            'recent' => $user->isAdmin()
                ? ActivityLog::with('user')->latest('id')->limit(8)->get()
                : ActivityLog::with('user')->where('user_id', $user->id)->latest('id')->limit(8)->get(),
        ])->layout('components.layouts.admin', ['title' => 'Обзор']);
    }
}
