<?php

use App\Support\UiStrings;
use Database\Seeders\UiStringSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Owner decision: the decorative scale display and sample label in the home hero carry no
 * currency. Rewords the readout captions and its screen-reader description, and removes the
 * label's currency word "lbl.cur". Also rewords the trust block lead and the model page
 * "works with" note, which described placeholder frames. Fresh databases get the same texts
 * from UiStringSeeder (UiStringSeeder::REWORDED).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (UiStringSeeder::REWORDED as $key => $text) {
            DB::table('ui_strings')->where('key', $key)->update($text + ['updated_at' => now()]);
        }

        DB::table('ui_strings')->where('key', 'lbl.cur')->delete();
        UiStrings::flush();
    }

    public function down(): void
    {
        // The old wording named the currency; it is not restored.
    }
};
