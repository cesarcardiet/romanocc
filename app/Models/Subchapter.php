<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Law;
use App\Models\Chapter;

class Subchapter extends Model
{
    protected $fillable = ['law_id', 'chapter_id', 'subchapter_number', 'subchapter_title'];

    public function law()
    {
        return $this->belongsTo(Law::class, 'law_id', 'id');
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class, 'chapter_id', 'id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'subchapter_id', 'id');
    }
}
