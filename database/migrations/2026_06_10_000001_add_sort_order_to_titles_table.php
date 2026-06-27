<?php

use App\Services\LegalOrderService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('title');
        });

        $titles = DB::table('titles')->orderBy('law_id')->orderBy('id')->get();
        $unnumberedCounters = [];

        foreach ($titles as $title) {
            $parsed = LegalOrderService::parseOrderFromText($title->title);

            if ($parsed !== null) {
                $sortOrder = $parsed;
            } else {
                $lawId = $title->law_id;
                $unnumberedCounters[$lawId] = ($unnumberedCounters[$lawId] ?? 900);
                $sortOrder = $unnumberedCounters[$lawId]++;
            }

            DB::table('titles')->where('id', $title->id)->update(['sort_order' => $sortOrder]);
        }
    }

    public function down(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
