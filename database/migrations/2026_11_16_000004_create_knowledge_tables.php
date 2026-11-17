<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable()->unique();
            $table->foreignId('scale_model_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['manual', 'software', 'sdk']);
            $table->json('title')->nullable();
            $table->string('format', 20)->nullable();  // PDF, ZIP
            $table->string('version', 30)->nullable();
            $table->date('released_on')->nullable();
            $table->json('size_label')->nullable();    // {ru: "3,1 МБ", uz: "3,1 MB", en: "3.1 MB"}
            $table->string('file_path')->nullable();
            $table->boolean('is_placeholder')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable()->unique();
            $table->foreignId('family_id')->nullable()->constrained()->nullOnDelete();
            $table->json('title');
            $table->string('url')->nullable();
            $table->boolean('is_placeholder')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('faq_topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_topic_id')->constrained()->cascadeOnDelete();
            $table->json('question');
            $table->json('answer');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('guide_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('does');
            $table->json('fits');
            $table->json('limit');
            $table->json('cost');                      // running-cost level in words (low / medium / high), not money
            $table->json('peak');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('guide_checks', function (Blueprint $table) {
            $table->id();
            $table->json('text');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('chooser_options', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();           // shop | supermarket | market
            $table->foreignId('family_id')->nullable()->constrained()->nullOnDelete();
            $table->json('why');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('chooser_option_scale_model', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chooser_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scale_model_id')->constrained()->cascadeOnDelete();
            $table->json('note')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->unique(['chooser_option_id', 'scale_model_id'], 'chooser_model_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chooser_option_scale_model');
        Schema::dropIfExists('chooser_options');
        Schema::dropIfExists('guide_checks');
        Schema::dropIfExists('guide_entries');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('faq_topics');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('documents');
    }
};
