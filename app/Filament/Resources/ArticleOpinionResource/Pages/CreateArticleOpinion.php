<?php

namespace App\Filament\Resources\ArticleOpinionResource\Pages;

use App\Filament\Resources\ArticleOpinionResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Article;

class CreateArticleOpinion extends CreateRecord
{
    protected static string $resource = ArticleOpinionResource::class;
    
    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Crear Adición';
    }
    # redireccionar a la pagina de index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
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

    protected function afterCreate(): void
    {
        // Cargar las relaciones necesarias para la notificación
        $this->record->load(['article.law']);

        $notificationService = app(NotificationService::class);
        $notificationService->sendArticleOpinionCreatedNotification($this->record);

        Notification::make()
            ->title('Adición Creada')
            ->body("La adición ha sido creada correctamente.")
            ->success()
            ->send();
    }
}
