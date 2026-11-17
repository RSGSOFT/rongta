<?php

namespace App\Livewire\Admin\Leads;

use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\Lead;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** /admin/leads/{lead}: the contact and the chatbot conversations it started. */
class Show extends Component
{
    use RequiresStaff;

    #[Locked]
    public int $leadId;

    public function mount(Lead $lead): void
    {
        $this->leadId = $lead->id;
    }

    public function render(): View
    {
        $lead = Lead::with(['conversations' => fn ($q) => $q->withCount('messages')])->findOrFail($this->leadId);

        return view('livewire.admin.leads.show', [
            'lead' => $lead,
            'others' => Lead::where('phone', $lead->phone)->whereKeyNot($lead->id)->orderByDesc('id')->limit(10)->get(),
        ])->layout('components.layouts.admin', ['title' => 'Лид: '.$lead->first_name.' '.$lead->last_name]);
    }
}
