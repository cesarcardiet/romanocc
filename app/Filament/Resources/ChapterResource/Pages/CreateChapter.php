<?php

namespace App\Filament\Resources\ChapterResource\Pages;

use App\Filament\Resources\ChapterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChapter extends CreateRecord
{
    protected static string $resource = ChapterResource::class;
    
    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Crear Capítulo';
    }

    # Redirigir a la lista de capítulos
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    # Agregar un botón para regresar a la lista de capítulos
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
