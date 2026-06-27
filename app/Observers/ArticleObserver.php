<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\IndexCacheService;

class ArticleObserver
{
    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Article "restored" event.
     */
    public function restored(Article $article): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Article "force deleted" event.
     */
    public function forceDeleted(Article $article): void
    {
        IndexCacheService::clearAll();
    }
}
