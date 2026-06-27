<?php

namespace App\Filament\Resources\InformationAppResource\Pages;

use App\Filament\Resources\InformationAppResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInformationApp extends EditRecord
{
    protected static string $resource = InformationAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['url_terminos_y_condiciones']) && !empty($data['url_terminos_y_condiciones'])) {
            // Filament ya maneja el almacenamiento, solo guardamos el path relativo
            $data['url_terminos_y_condiciones'] = ltrim($data['url_terminos_y_condiciones'], 'storage/');
        }

        if (isset($data['url_politica_de_privacidad']) && !empty($data['url_politica_de_privacidad'])) {
            // Filament ya maneja el almacenamiento, solo guardamos el path relativo
            $data['url_politica_de_privacidad'] = ltrim($data['url_politica_de_privacidad'], 'storage/');
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $notificationService = app(NotificationService::class);
        $originalData = $this->record->getOriginal();
        $dirty = $this->record->getDirty();

        // El accessor del modelo ya devuelve la URL completa; no concatenar baseUrl
        if (isset($dirty['url_terminos_y_condiciones']) &&
            !empty($this->record->url_terminos_y_condiciones) &&
            $this->record->url_terminos_y_condiciones !== ($originalData['url_terminos_y_condiciones'] ?? null)) {
            $notificationService->sendTermsUpdatedNotification($this->record->url_terminos_y_condiciones);
        }

        if (isset($dirty['url_politica_de_privacidad']) &&
            !empty($this->record->url_politica_de_privacidad) &&
            $this->record->url_politica_de_privacidad !== ($originalData['url_politica_de_privacidad'] ?? null)) {
            $notificationService->sendPrivacyUpdatedNotification($this->record->url_politica_de_privacidad);
        }
    }
}
