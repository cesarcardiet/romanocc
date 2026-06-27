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
        Schema::create('subchapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained('laws')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('chapter_id')->constrained('chapters')->onUpdate('cascade')->onDelete('cascade');
            $table->string('subchapter_number');
            $table->string('subchapter_title');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subchapters');
    }
};
