<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Law;
use App\Models\Title;
use App\Models\Article;

class Chapter extends Model
{
    protected $fillable = ['law_id', 'title_id', 'chapter_number', 'chapter_title'];

    public function law()
    {
        return $this->belongsTo(Law::class, 'law_id', 'id');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'title_id', 'id');
    }

    public function subchapters()
    {
        return $this->hasMany(Subchapter::class, 'chapter_id', 'id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'chapter_id', 'id')
            ->whereNull('subchapter_id')
            ->orderBy('article_number', 'asc');
    }

}
