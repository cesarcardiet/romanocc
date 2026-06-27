<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

class SendTestNotification extends Command
{
    protected $signature = 'notification:test {user_id? : ID del usuario destino}';

    protected $description = 'Envía una notificación de prueba por FCM y la guarda en la base de datos';

    public function handle(FcmService $fcmService): int
    {
        try {
            $fcmService->testConnection();
            $this->line('  Firebase: <fg=green>conexión OK</>');
        } catch (\Exception $e) {
            $this->error('Firebase no configurado: ' . $e->getMessage());

            return self::FAILURE;
        }

        $userId = $this->argument('user_id');

        if (! $userId) {
            $users = User::query()->orderBy('name')->get();
            if ($users->isEmpty()) {
                $this->error('No hay usuarios en la base de datos.');

                return self::FAILURE;
            }

            $userId = $this->choice(
                'Selecciona el usuario:',
                $users->pluck('name', 'id')->toArray()
            );
        }

        $user = User::find($userId);

        if (! $user) {
            $this->error("Usuario #{$userId} no encontrado.");

            return self::FAILURE;
        }

        $tokens = $user->getActiveFcmTokens();
        if (empty($tokens)) {
            $this->warn("El usuario «{$user->name}» no tiene tokens FCM activos. La app debe registrar el token (POST /api/user/fcm-token).");

            return self::FAILURE;
        }

        $this->info("Enviando notificación de prueba a: {$user->name} (ID {$user->id})");

        $title = 'Notificación de prueba';
        $message = 'Esta es una notificación de prueba desde ROMANOCC';
        $data = [
            'action' => 'test',
            'test_data' => 'Hello from backend!',
            'timestamp' => now()->toIso8601String(),
        ];

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
            'fcm_sent' => false,
        ]);

        $success = $fcmService->sendTestToUser($user, $title, $message, $data);

        $stats = $fcmService->getLastSendStats();

        if ($success) {
            $notification->markAsSent();
            $this->info('✅ FCM aceptó el envío.');
            $this->line("   Tokens OK: {$stats['success_count']} / {$stats['total_tokens']}");
            $this->line("   Notificación en BD: #{$notification->id} (fcm_sent=true)");

            return self::SUCCESS;
        }

        $this->error('❌ FCM no entregó el mensaje a ningún token.');
        $this->line("   Tokens fallidos: {$stats['failure_count']} / {$stats['total_tokens']}");
        $this->line("   Notificación en BD: #{$notification->id} (fcm_sent=false)");
        if ($error = $fcmService->getLastError()) {
            $this->line("   Error: {$error}");
        }

        return self::FAILURE;
    }
}
