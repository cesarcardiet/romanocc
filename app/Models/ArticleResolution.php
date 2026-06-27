<?php

namespace App\Models;

use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Model;

class ArticleResolution extends Model
{
    protected $fillable = ['article_id', 'user_id', 'name', 'url', 'url_pdf'];

    /**
     * URL pública del PDF (para panel y API).
     */
    public function getUrlPdfFullAttribute(): ?string
    {
        return StorageUrl::forPath($this->attributes['url_pdf'] ?? null);
    }

    protected static function booted(): void
    {
        static::saving(function (ArticleResolution $model) {
            if ($model->exists) {
                $incoming = $model->getAttributes()['url_pdf'] ?? null;
                $original = $model->getRawOriginal('url_pdf');
                if (($incoming === null || $incoming === '') && $original !== null && $original !== '') {
                    $model->setAttribute('url_pdf', $original);
                }
            }
        });
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    public function law()
    {
        return $this->hasOneThrough(Law::class, Article::class, 'id', 'id', 'article_id', 'law_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
