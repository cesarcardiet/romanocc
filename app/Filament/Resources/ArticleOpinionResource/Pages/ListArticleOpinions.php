<?php

namespace App\Filament\Resources\ArticleOpinionResource\Pages;

use App\Filament\Resources\ArticleOpinionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticleOpinions extends ListRecords
{
    protected static string $resource = ArticleOpinionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Adición')
                ->icon('heroicon-o-plus'),
        ];
    }
}
