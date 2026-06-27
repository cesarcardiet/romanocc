<?php

namespace App\Filament\Resources\InformationAppResource\Pages;

use App\Filament\Resources\InformationAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInformationApps extends ListRecords
{
    protected static string $resource = InformationAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
