<?php

namespace App\Filament\Resources\InformationAppResource\Pages;

use App\Filament\Resources\InformationAppResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInformationApp extends CreateRecord
{
    protected static string $resource = InformationAppResource::class;

    protected function afterCreate(): void
    {
        $notificationService = app(NotificationService::class);

        // El accessor del modelo ya devuelve la URL completa; no concatenar baseUrl
        if ($this->record->url_terminos_y_condiciones) {
            $notificationService->sendTermsUpdatedNotification($this->record->url_terminos_y_condiciones);
        }

        if ($this->record->url_politica_de_privacidad) {
            $notificationService->sendPrivacyUpdatedNotification($this->record->url_politica_de_privacidad);
        }
    }
}
