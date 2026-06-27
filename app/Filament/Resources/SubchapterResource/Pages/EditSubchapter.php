<?php

namespace App\Filament\Resources\SubchapterResource\Pages;

use App\Filament\Resources\SubchapterResource;
use App\Models\Chapter;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\NotificationService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditSubchapter extends EditRecord
{
    protected static string $resource = SubchapterResource::class;

    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Editar Subcapítulo';
    }

    # Asegurar que law_id se establezca correctamente
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Asegurar que law_id se establezca correctamente
        if (isset($data['chapter_id'])) {
            $chapter = Chapter::find($data['chapter_id']);
            if ($chapter) {
                $data['law_id'] = $chapter->law_id;
            }
        }
        
        return $data;
    }

    # Redirigir a la lista de subcapítulos
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    # Agregar un botón para regresar a la lista de subcapítulos
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
        $notificationService->sendSubchapterUpdatedNotification($updatedRecord);

        Notification::make()
            ->title('Subcapítulo Actualizado')
            ->body("El subcapítulo **{$updatedRecord->title}** ha sido actualizado correctamente.")
            ->success()
            ->send();
        
        return $updatedRecord;
    }
}
