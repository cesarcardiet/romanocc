<?php

namespace App\Observers;

use App\Models\Chapter;
use App\Services\IndexCacheService;

class ChapterObserver
{
    /**
     * Handle the Chapter "created" event.
     */
    public function created(Chapter $chapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Chapter "updated" event.
     */
    public function updated(Chapter $chapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Chapter "deleted" event.
     */
    public function deleted(Chapter $chapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Chapter "restored" event.
     */
    public function restored(Chapter $chapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Chapter "force deleted" event.
     */
    public function forceDeleted(Chapter $chapter): void
    {
        IndexCacheService::clearAll();
    }
}
