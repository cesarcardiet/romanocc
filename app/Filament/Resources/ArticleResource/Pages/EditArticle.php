<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\NotificationService;
use Filament\Notifications\Notification;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

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

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updatedRecord = parent::handleRecordUpdate($record, $data);

        $notificationService = app(NotificationService::class);
        $notificationService->sendArticleUpdatedNotification($updatedRecord);

        Notification::make()
            ->title('Artículo Actualizado')
            ->body("El artículo **{$updatedRecord->article_title}** ha sido actualizado correctamente.")
            ->success()
            ->send();
        
        return $updatedRecord;
    }
}
