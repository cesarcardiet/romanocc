<?php

namespace App\Observers;

use App\Models\Title;
use App\Services\IndexCacheService;

class TitleObserver
{
    /**
     * Handle the Title "created" event.
     */
    public function created(Title $title): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Title "updated" event.
     */
    public function updated(Title $title): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Title "deleted" event.
     */
    public function deleted(Title $title): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Title "restored" event.
     */
    public function restored(Title $title): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Title "force deleted" event.
     */
    public function forceDeleted(Title $title): void
    {
        IndexCacheService::clearAll();
    }
}
