<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advantages and terms listed on the "For partners" page. Staff enter them in the admin panel;
 * nothing is seeded, because these are RSG's own commitments and must not be invented.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_points', function (Blueprint $table) {
            $table->id();
            $table->string('kind');                    // advantage | term
            $table->json('title');
            $table->json('body')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['kind', 'is_published', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_points');
    }
};
