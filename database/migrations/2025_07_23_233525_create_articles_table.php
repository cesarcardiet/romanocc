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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained('laws')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('title_id')->constrained('titles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('subchapter_id')->nullable()->constrained('subchapters')->onUpdate('cascade')->onDelete('cascade');
            $table->string('article_number');
            $table->string('article_title');
            $table->text('article_content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
