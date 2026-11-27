<?php

use App\Models\Setting;
use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What happened to each imported scale after the import: handed to a dealer (which starts the
 * warranty), accepted for service, repaired, returned. Also the warranty length setting
 * (owner decision 2026-11-24: one period for every model, 12 months by default) and the
 * strings of the public serial number check.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_number_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                       // dealer | service_in | service_done | service_out
            $table->foreignId('partner_id')->nullable()->constrained()->restrictOnDelete();        // the dealer, for "dealer"
            $table->foreignId('service_case_id')->nullable()->constrained()->nullOnDelete();       // the job, for service steps
            $table->date('happened_on');
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();               // staff member who recorded it
            $table->timestamps();

            $table->index(['serial_number_id', 'happened_on']);
            $table->index(['type', 'partner_id']);
        });

        if (Setting::where('key', 'warranty_months')->doesntExist()) {
            Setting::write('warranty_months', 12);
        }

        foreach ($this->keys() as $key) {
            UiString::firstOrCreate(['key' => $key], SiteBStringSeeder::STRINGS[$key]);
        }
        UiStrings::flush();
    }

    public function down(): void
    {
        UiString::whereIn('key', $this->keys())->delete();
        UiStrings::flush();
        Setting::where('key', 'warranty_months')->delete();

        Schema::dropIfExists('serial_movements');
    }

    /** @return list<string> */
    private function keys(): array
    {
        return array_values(array_filter(array_keys(SiteBStringSeeder::STRINGS), fn (string $key) => str_starts_with($key, 'sn.')));
    }
};
