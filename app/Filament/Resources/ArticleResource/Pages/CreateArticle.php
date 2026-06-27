<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;
    
    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Crear Artículo';
    }
    

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validar y sanitizar article_title (trim espacios en blanco)
        $articleTitle = trim($data['article_title'] ?? '');
        
        // Si está vacío después del trim, lanzar error de validación
        if (empty($articleTitle)) {
            \Filament\Notifications\Notification::make()
                ->title('Error de validación')
                ->body('El título del artículo es obligatorio.')
                ->danger()
                ->send();
            throw new \Illuminate\Validation\ValidationException(
                validator([], ['article_title' => 'required'])->errors()->add('article_title', 'El título del artículo es obligatorio.')
            );
        }

        // Asignar el valor sanitizado
        $data['article_title'] = $articleTitle;

        return $data;
    }
}
