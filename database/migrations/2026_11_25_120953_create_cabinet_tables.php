<?php

use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner cabinet (owner decision 2026-11-25, replacing "no partner login" of 2026-11-17):
 * a dealer or a service centre registers itself, an admin approves the account and links it
 * to a partner. What the account may do follows the partner's "sales" and "service" offers.
 * Also: who recorded a service step, and a service centre's requests to see a scale's history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabinet_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();   // set by an admin on approval
            $table->string('company');
            $table->string('contact_name');
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('wants_sales')->default(false);       // what the company said about itself
            $table->boolean('wants_service')->default(false);
            $table->string('locale', 2)->default('ru');
            $table->string('status', 10)->default('pending')->index();   // pending | approved | blocked
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('serial_movements', function (Blueprint $table) {
            // The cabinet account that recorded a service step; partner_id then holds its service centre.
            $table->foreignId('cabinet_account_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('serial_detail_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();            // the service centre asking
            $table->foreignId('cabinet_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_number_id')->constrained()->cascadeOnDelete();
            $table->string('note', 500)->nullable();
            $table->string('status', 10)->default('new')->index();   // new | granted | refused
            $table->string('answer', 500)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['partner_id', 'serial_number_id']);
        });

        foreach ($this->keys() as $key) {
            UiString::firstOrCreate(['key' => $key], SiteBStringSeeder::STRINGS[$key]);
        }
        UiStrings::flush();
    }

    public function down(): void
    {
        UiString::whereIn('key', $this->keys())->delete();
        UiStrings::flush();

        Schema::dropIfExists('serial_detail_requests');
        Schema::table('serial_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('cabinet_account_id'));
        Schema::dropIfExists('cabinet_accounts');
    }

    /** @return list<string> */
    private function keys(): array
    {
        return array_values(array_filter(array_keys(SiteBStringSeeder::STRINGS), fn (string $key) => str_starts_with($key, 'cab.')));
    }
};
