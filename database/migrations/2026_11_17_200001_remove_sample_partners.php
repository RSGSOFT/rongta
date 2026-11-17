<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Owner decision: the 17 sample resellers and integrators from the prototype are removed.
 * The Dealers page then lists only RSG until real partners are added in the admin panel.
 * SampleDataSeeder no longer creates them. A copy of the removed rows was exported to
 * .claude/backups/removed-sample-partners.json before this ran on the dev database.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('partners')->where('is_placeholder', true)->delete();
    }

    public function down(): void
    {
        // The sample rows are not recreated; see the backup file named above.
    }
};
