<?php

namespace App\Filament\Resources\ArticleCommentResource\Pages;

use App\Filament\Resources\ArticleCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleComment extends CreateRecord
{
    protected static string $resource = ArticleCommentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Regresar')
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
