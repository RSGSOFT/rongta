<?php

use App\Support\UiStrings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes interface strings left over from the prototype that no page uses: prototype
 * notices, sample-data notes, price wording and the scripted demo-bot answers.
 * UiStringSeeder skips the same keys, so they do not come back.
 */
return new class extends Migration
{
    private const KEYS = [
        'buy.lead', 'pa.i.terms', 'ftr.note', 'call.note', 'file.proto', 'file.sample', 'file.samplenote',
        'dl.samplenote', 'ph.placeholder', 'pa.demo', 'pa.proto', 'pa.preview', 'pf.proto', 'pf.done.p',
        'kb.sample', 'chat.proto', 'chat.sub',
        'chat.a.choose', 'chat.a.dealers', 'chat.a.docs', 'chat.a.fallback', 'chat.a.human', 'chat.a.partner',
        'chat.a.pos', 'chat.a.reco', 'chat.a.reco.end', 'chat.a.warranty',
    ];

    public function up(): void
    {
        DB::table('ui_strings')->whereIn('key', self::KEYS)->delete();
        UiStrings::flush();
    }

    public function down(): void
    {
        // The removed texts are not kept in code; UiStringSeeder no longer imports them.
    }
};
