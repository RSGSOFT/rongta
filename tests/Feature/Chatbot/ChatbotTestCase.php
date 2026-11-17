<?php

namespace Tests\Feature\Chatbot;

use App\Livewire\Chat\Widget;
use App\Models\ChatConversation;
use App\Models\Lead;
use Database\Seeders\ChatbotStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ChatbotTestCase extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(ChatbotStringSeeder::class);
    }

    /** A lead that has passed the gate, with its conversation remembered in the session. */
    protected function passGate(string $locale = 'ru', string $ip = '127.0.0.1'): ChatConversation
    {
        $lead = Lead::create([
            'first_name' => 'Aziz', 'last_name' => 'Karimov', 'phone' => '+998901234567',
            'locale' => $locale, 'source' => 'chatbot',
        ]);
        $conversation = ChatConversation::create(['lead_id' => $lead->id, 'locale' => $locale, 'ip' => $ip]);
        session([Widget::SESSION_LEAD => $lead->id, Widget::SESSION_CONVERSATION => $conversation->id]);

        return $conversation;
    }
}
