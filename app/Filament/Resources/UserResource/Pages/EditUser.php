<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Editar Usuario';
    }

    # Redirigir a la lista de títulos
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    # Agregar un botón para regresar a la lista de títulos
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
