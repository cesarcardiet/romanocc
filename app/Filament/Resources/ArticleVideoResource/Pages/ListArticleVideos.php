<?php

namespace App\Filament\Resources\ArticleVideoResource\Pages;

use App\Filament\Resources\ArticleVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticleVideos extends ListRecords
{
    protected static string $resource = ArticleVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
