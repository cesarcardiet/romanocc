<?php

use App\Services\LegalOrderService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('titles', 'sort_order')) {
            return;
        }

        $titles = DB::table('titles')->orderBy('law_id')->orderBy('id')->get();

        foreach ($titles as $title) {
            DB::table('titles')->where('id', $title->id)->update([
                'sort_order' => LegalOrderService::effectiveTitleOrder($title),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
