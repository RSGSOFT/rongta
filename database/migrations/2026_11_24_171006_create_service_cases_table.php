<?php

use App\Models\Family;
use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\ServiceCaseSeeder;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service list: service and repair jobs with the amount RSG charges, in soʻm. Owner-approved
 * exception to the "no prices" rule (2026-11-24), for service fees only. The starter jobs are
 * loaded without amounts; staff enter them, and a job without an amount stays off the site.
 */
return new class extends Migration
{
    private const KEYS = ['svc.h', 'svc.lead', 'svc.all', 'svc.col.job', 'svc.col.amount', 'svc.amount', 'svc.amount.from', 'svc.updated'];

    public function up(): void
    {
        Schema::create('service_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->nullable()->constrained()->nullOnDelete();   // null = every scale
            $table->json('title');
            $table->json('note')->nullable();
            $table->unsignedInteger('amount')->nullable();   // soʻm; null = not set, hidden on the site
            $table->boolean('is_from')->default(false);      // "from 150 000" instead of a fixed amount
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'sort']);
        });

        // A fresh database has no families yet: there DatabaseSeeder loads the jobs after the catalog.
        if (Family::query()->exists()) {
            (new ServiceCaseSeeder)->run();
        }

        foreach (self::KEYS as $key) {
            UiString::firstOrCreate(['key' => $key], SiteBStringSeeder::STRINGS[$key]);
        }
        UiStrings::flush();
    }

    public function down(): void
    {
        UiString::whereIn('key', self::KEYS)->delete();
        UiStrings::flush();

        Schema::dropIfExists('service_cases');
    }
};
