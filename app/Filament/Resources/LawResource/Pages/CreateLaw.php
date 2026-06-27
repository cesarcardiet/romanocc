<?php

namespace App\Filament\Resources\LawResource\Pages;

use App\Filament\Resources\LawResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLaw extends CreateRecord
{
    protected static string $resource = LawResource::class;
    
    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Crear Ley o Reglamento';
    }

    # Redirigir a la lista de leyes
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    
    # Agregar un botón para regresar a la lista de leyes
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
