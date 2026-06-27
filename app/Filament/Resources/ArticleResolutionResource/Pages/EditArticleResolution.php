<?php

namespace App\Filament\Resources\ArticleResolutionResource\Pages;

use App\Filament\Resources\ArticleResolutionResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use App\Models\Article;

class EditArticleResolution extends EditRecord
{
    protected static string $resource = ArticleResolutionResource::class;

    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Editar Resolución';
    }
    # redireccionar a la pagina de index    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Asegurar que law_id se establezca correctamente desde el artículo
        if (isset($data['article_id']) && !isset($data['law_id'])) {
            $article = \App\Models\Article::find($data['article_id']);
            if ($article) {
                $data['law_id'] = $article->law_id;
            }
        }
        
        return $data;
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

    # manejar la actualización cuando se cambia la ley
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si se cambió la ley, verificar que el artículo seleccionado pertenezca a esa ley
        if (isset($data['law_id']) && isset($data['article_id'])) {
            $article = Article::find($data['article_id']);
            if ($article && $article->law_id != $data['law_id']) {
                // Si el artículo no pertenece a la ley seleccionada, limpiar el artículo
                unset($data['article_id']);
            }
        }
        
        // Si no hay article_id pero hay law_id, limpiar law_id ya que no es un campo directo del modelo
        if (!isset($data['article_id']) && isset($data['law_id'])) {
            unset($data['law_id']);
        }
        
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updatedRecord = parent::handleRecordUpdate($record, $data);

        // Cargar las relaciones necesarias para la notificación
        $updatedRecord->load(['article.law']);

        $notificationService = app(NotificationService::class);
        $notificationService->sendArticleResolutionUpdatedNotification($updatedRecord);

        Notification::make()
            ->title('Resolución Actualizada')
            ->body("La resolución ha sido actualizada correctamente.")
            ->success()
            ->send();
        
        return $updatedRecord;
    }
}
