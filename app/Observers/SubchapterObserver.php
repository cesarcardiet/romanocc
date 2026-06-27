<?php

namespace App\Observers;

use App\Models\Subchapter;
use App\Services\IndexCacheService;

class SubchapterObserver
{
    /**
     * Handle the Subchapter "created" event.
     */
    public function created(Subchapter $subchapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Subchapter "updated" event.
     */
    public function updated(Subchapter $subchapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Subchapter "deleted" event.
     */
    public function deleted(Subchapter $subchapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Subchapter "restored" event.
     */
    public function restored(Subchapter $subchapter): void
    {
        IndexCacheService::clearAll();
    }

    /**
     * Handle the Subchapter "force deleted" event.
     */
    public function forceDeleted(Subchapter $subchapter): void
    {
        IndexCacheService::clearAll();
    }
}
