<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff-written (or staff-reviewed) knowledge the chatbot may answer from.
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->json('title');                                 // {ru, uz, en}
            $table->json('body');                                  // {ru, uz, en}
            $table->foreignId('scale_model_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('family_id')->nullable()->constrained()->nullOnDelete();
            $table->json('tags')->nullable();                      // list of strings
            $table->boolean('is_published')->default(false);
            $table->string('source', 20)->default('manual');       // manual | pdf | conversation
            $table->string('source_ref')->nullable();              // uploaded file name, for pdf drafts
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('is_published');
        });

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('page_url', 2048)->nullable();
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('unanswered_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index('ip');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10);                            // user | assistant
            $table->text('content');
            // assistant rows: answered | unanswered | refused | unavailable | blocked
            $table->string('status', 20)->nullable();
            // user rows: the bot could not answer this question
            $table->boolean('is_unanswered')->default(false);
            $table->string('unanswered_reason', 40)->nullable();
            $table->foreignId('knowledge_article_id')->nullable()->constrained()->nullOnDelete();
            // assistant rows: usage of the Claude calls that produced the reply
            $table->string('model', 60)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('cache_read_tokens')->nullable();
            $table->unsignedInteger('cache_write_tokens')->nullable();
            $table->json('tool_calls')->nullable();                // [{name, input}]
            $table->timestamps();
            $table->index(['role', 'created_at']);
            $table->index('is_unanswered');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('knowledge_articles');
    }
};
