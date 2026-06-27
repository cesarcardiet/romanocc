<?php

namespace App\Filament\Resources\ArticleResolutionResource\Pages;

use App\Filament\Resources\ArticleResolutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticleResolutions extends ListRecords
{
    protected static string $resource = ArticleResolutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Resolución')
                ->icon('heroicon-o-plus'),
        ];
    }
}
