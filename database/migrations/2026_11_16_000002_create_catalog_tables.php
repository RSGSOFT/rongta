<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Trilingual text columns are JSON: {"ru": "...", "uz": "...", "en": "..."}.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();          // label | ai | comm
            $table->json('name');
            $table->json('line');                      // one-sentence description
            $table->string('pic')->nullable();         // illustration kind used for the family card
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('scale_models', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->string('name');                    // model name, same in every language
            $table->string('kind');                    // illustration kind: hang, pole, flat, c2ai, aipc, y2l, comm, c3
            $table->json('illustration')->nullable();  // {head: string|null, cam: bool, receipt: bool, dual: bool}
            $table->boolean('is_aurora')->default(false);
            $table->enum('label_print', ['yes', 'no', 'receipt'])->default('no');
            $table->json('conn');                      // {eth: std|opt, rs232, usb, wifi, bt}
            $table->json('type');
            $table->json('headline');
            $table->json('intro');
            $table->string('source_url')->nullable();
            $table->string('image')->nullable();       // main photo path; null = use the drawn illustration
            $table->json('gallery')->nullable();       // list of extra image paths
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('model_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scale_model_id')->constrained()->cascadeOnDelete();
            $table->string('icon')->nullable();
            $table->json('title');
            $table->json('text');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('model_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scale_model_id')->constrained()->cascadeOnDelete();
            $table->string('group');                   // weighing, display, system, printer, conn, power, physical, compliance, box
            $table->string('key');                     // maxCap, minCap, ... (label = ui('spec.'.key))
            $table->json('value');                     // {ru,uz,en} | {parts: [[label, value], ...]} | {v, note}
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['scale_model_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_specs');
        Schema::dropIfExists('model_features');
        Schema::dropIfExists('scale_models');
        Schema::dropIfExists('families');
    }
};
