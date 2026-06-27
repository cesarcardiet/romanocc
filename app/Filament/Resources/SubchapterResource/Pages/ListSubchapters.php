<?php

namespace App\Filament\Resources\SubchapterResource\Pages;

use App\Filament\Resources\SubchapterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubchapters extends ListRecords
{
    protected static string $resource = SubchapterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Subcapítulo')
                ->icon('heroicon-o-plus'),
        ];
    }
}
