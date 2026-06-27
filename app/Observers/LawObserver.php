<?php

namespace App\Observers;

use App\Models\Law;
use App\Services\IndexCacheService;

class LawObserver
{
    /**
     * Handle the Law "created" event.
     */
    public function created(Law $law): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Law "updated" event.
     */
    public function updated(Law $law): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Law "deleted" event.
     */
    public function deleted(Law $law): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Law "restored" event.
     */
    public function restored(Law $law): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Law "force deleted" event.
     */
    public function forceDeleted(Law $law): void
    {
        IndexCacheService::clearAll();
    }
}
