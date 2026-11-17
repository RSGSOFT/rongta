<?php

use App\Support\UiStrings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner decision: there is no partner login or partner area. Drops the partner_users table and
 * the partner-area interface strings (every "pa." key and "ps.login"); UiStringSeeder and
 * SiteBStringSeeder no longer create them. The removed strings were exported to
 * .claude/backups/removed-partner-strings.json before this ran on the dev database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('partner_users');

        DB::table('ui_strings')
            ->where(fn ($q) => $q->where('key', 'like', 'pa.%')->orWhere('key', 'ps.login'))
            ->delete();
        UiStrings::flush();
    }

    public function down(): void
    {
        if (! Schema::hasTable('partner_users')) {
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
        }
    }
};
