<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleVisit extends Model
{
    protected $fillable = ['article_id', 'user_id', 'ip_address'];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
