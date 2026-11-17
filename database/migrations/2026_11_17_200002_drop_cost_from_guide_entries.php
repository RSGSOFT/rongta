<?php

use App\Support\UiStrings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner decision: the buying guide has no "cost" row any more (page, admin form, chatbot).
 * Drops the column and its row label "g.cost"; UiStringSeeder skips that key.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('guide_entries', 'cost')) {
            Schema::table('guide_entries', function (Blueprint $table) {
                $table->dropColumn('cost');
            });
        }

        DB::table('ui_strings')->where('key', 'g.cost')->delete();
        UiStrings::flush();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('guide_entries', 'cost')) {
            Schema::table('guide_entries', function (Blueprint $table) {
                $table->json('cost')->nullable()->after('limit');
            });
        }
    }
};
