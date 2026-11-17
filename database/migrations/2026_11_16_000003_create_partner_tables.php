<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('short');
            $table->unsignedTinyInteger('map_col');    // position on the schematic region map
            $table->unsignedTinyInteger('map_row');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->json('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable()->unique();
            $table->enum('kind', ['reseller', 'integrator']);
            $table->json('name');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->json('offers')->nullable();        // list: sales, install, service, integration, support
            $table->string('phone')->nullable();
            $table->string('telegram')->nullable();
            $table->json('address')->nullable();
            $table->boolean('is_placeholder')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('partner_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('login')->unique();
            $table->string('password');
            $table->enum('type', ['reseller', 'integrator']);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partner_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('contact_name');
            $table->string('phone');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);                // reseller | integrator
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new'); // new | in_progress | done | rejected
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('locale', 5)->nullable();
            $table->string('source')->nullable();      // e.g. chatbot
            $table->string('page_url', 2048)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('partner_requests');
        Schema::dropIfExists('partner_users');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('regions');
    }
};
