<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained('laws')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('title_id')->nullable()->constrained('titles')->onUpdate('cascade')->onDelete('cascade');
            $table->string('chapter_number');
            $table->string('chapter_title');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
