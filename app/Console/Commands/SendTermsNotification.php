<?php

namespace App\Console\Commands;

use App\Models\InformationApp;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendTermsNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-terms
                            {type : terms or privacy}
                            {--user= : Solo un usuario (prueba); si no se indica, envía a todos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificación de términos o privacidad (masivo). Para pruebas usa notification:simulate';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        if (!in_array($type, ['terms', 'privacy'])) {
            $this->error('Type must be either "terms" or "privacy"');
            return 1;
        }

        $onlyUserId = $this->option('user') !== null && $this->option('user') !== ''
            ? (int) $this->option('user')
            : null;

        $info = InformationApp::query()->first();
        $termsUrl = $info?->url_terminos_y_condiciones;
        $privacyUrl = $info?->url_politica_de_privacidad;

        $target = $onlyUserId ? "usuario #{$onlyUserId}" : 'todos los usuarios';
        $this->info("Enviando notificación de {$type} a {$target}...");

        try {
            if ($type === 'terms') {
                $this->notificationService->sendTermsUpdatedNotification($termsUrl, $onlyUserId);
                $this->info('Notificaciones de términos enviadas.');
            } else {
                $this->notificationService->sendPrivacyUpdatedNotification($privacyUrl, $onlyUserId);
                $this->info('Notificaciones de privacidad enviadas.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error sending notifications: " . $e->getMessage());
            return 1;
        }
    }
}
