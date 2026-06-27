<?php

namespace App\Models;

use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Model;

class ArticleFile extends Model
{
    protected $fillable = [
        'article_id',
        'file_path',
    ];

    protected static function booted(): void
    {
        static::saving(function (ArticleFile $model) {
            if ($model->exists) {
                $incoming = $model->getAttributes()['file_path'] ?? null;
                $original = $model->getRawOriginal('file_path');
                if (($incoming === null || $incoming === '') && $original !== null && $original !== '') {
                    $model->setAttribute('file_path', $original);
                }
            }
        });
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return StorageUrl::forPath($this->attributes['file_path'] ?? null);
    }

    public function getFileNameAttribute(): string
    {
        $rawPath = $this->attributes['file_path'] ?? '';

        return basename($rawPath);
    }
}
