<?php

namespace App\Filament\Resources\ChapterResource\Pages;

use App\Filament\Resources\ChapterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificationService;
use Filament\Notifications\Notification;

class EditChapter extends EditRecord
{
    protected static string $resource = ChapterResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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

        /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updatedRecord = parent::handleRecordUpdate($record, $data);

        $notificationService = app(NotificationService::class);
        $notificationService->sendChapterUpdatedNotification($updatedRecord);

        Notification::make()
            ->title('Capítulo Actualizado')
            ->body("El capítulo **{$updatedRecord->title}** ha sido actualizado correctamente.")
            ->success()
            ->send();
        
        return $updatedRecord;
    }
}
