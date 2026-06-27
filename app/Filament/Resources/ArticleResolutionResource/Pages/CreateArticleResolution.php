<?php

namespace App\Filament\Resources\ArticleResolutionResource\Pages;

use App\Filament\Resources\ArticleResolutionResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Article;

class CreateArticleResolution extends CreateRecord
{
    protected static string $resource = ArticleResolutionResource::class;
    
    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Crear Resolución';
    }
    # redireccionar a la pagina de index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    # agregar el boton de regresar a la pagina de index
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
    # agregar el usuario que crea la resolución
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        
        // Verificar que el artículo seleccionado pertenezca a la ley seleccionada
        if (isset($data['law_id']) && isset($data['article_id'])) {
            $article = Article::find($data['article_id']);
            if ($article && $article->law_id != $data['law_id']) {
                // Si el artículo no pertenece a la ley seleccionada, limpiar el artículo
                unset($data['article_id']);
            }
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Cargar las relaciones necesarias para la notificación
        $this->record->load(['article.law']);

        $notificationService = app(NotificationService::class);
        $notificationService->sendArticleResolutionCreatedNotification($this->record);

        Notification::make()
            ->title('Resolución Creada')
            ->body("La resolución ha sido creada correctamente.")
            ->success()
            ->send();
    }
}
