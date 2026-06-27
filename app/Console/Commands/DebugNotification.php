<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DebugNotification extends Command
{
    protected $signature = 'notification:debug {user_id : ID del usuario a diagnosticar}
                            {--no-send : Solo diagnóstico, sin enviar notificación de prueba}';

    protected $description = 'Diagnóstico FCM: credenciales, tokens del usuario y envío de prueba opcional';

    public function handle(FcmService $fcmService): int
    {
        $userId = (int) $this->argument('user_id');
        $user = User::find($userId);

        $this->newLine();
        $this->components->info('Diagnóstico de notificaciones push (FCM)');
        $this->newLine();

        // 1. Firebase / credenciales
        $this->line('<fg=cyan>── Firebase ──</>');
        $credentialsPath = storage_path('app/firebase/service-account-key.json');
        if (File::exists($credentialsPath)) {
            $this->line('  Archivo credenciales: <fg=green>' . $credentialsPath . '</>');
        } else {
            $this->line('  Archivo credenciales: <fg=yellow>no existe</> (' . $credentialsPath . ')');
            $this->line('  Se usarán variables FIREBASE_* o descarga automática al arrancar.');
        }

        $this->line('  Proyecto: ' . (env('FIREBASE_PROJECT_ID') ?: env('FIREBASE_PROJECT', 'romanocc-4114f')));
        $this->line('  Sender ID (.env): ' . (env('FIREBASE_SENDER_ID') ?: '(no definido)'));

        try {
            $fcmService->testConnection();
            $this->line('  Conexión Messaging: <fg=green>OK</>');
        } catch (\Throwable $e) {
            $this->line('  Conexión Messaging: <fg=red>FALLO</>');
            $this->line('  → ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        // 2. Usuario
        $this->line('<fg=cyan>── Usuario ──</>');
        if (! $user) {
            $this->error("  Usuario #{$userId} no encontrado.");

            return self::FAILURE;
        }

        $this->line("  ID: {$user->id}");
        $this->line("  Nombre: {$user->name}");
        $this->line("  Email: {$user->email}");

        $this->newLine();

        // 3. Tokens FCM
        $this->line('<fg=cyan>── Tokens FCM ──</>');
        $allTokens = UserFcmToken::where('user_id', $userId)->orderByDesc('updated_at')->get();

        if ($allTokens->isEmpty()) {
            $this->warn('  No hay registros en user_fcm_tokens para este usuario.');
            $this->line('  La app móvil debe llamar POST /api/user/fcm-token tras iniciar sesión.');
        } else {
            $rows = $allTokens->map(fn ($t) => [
                $t->id,
                $t->is_active ? 'sí' : 'no',
                $t->device_type ?? '—',
                substr($t->fcm_token, 0, 24) . '…',
                $t->updated_at?->format('Y-m-d H:i:s') ?? '—',
            ])->toArray();

            $this->table(
                ['ID', 'Activo', 'Dispositivo', 'Token (inicio)', 'Actualizado'],
                $rows
            );

            $activeCount = $allTokens->where('is_active', true)->count();
            $this->line("  Activos: {$activeCount} / {$allTokens->count()}");
        }

        $activeTokens = $user->getActiveFcmTokens();

        $this->newLine();

        // 4. Últimas notificaciones en BD
        $this->line('<fg=cyan>── Últimas notificaciones (BD) ──</>');
        $recent = Notification::where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($recent->isEmpty()) {
            $this->line('  Sin notificaciones guardadas.');
        } else {
            $this->table(
                ['ID', 'Tipo', 'Título', 'fcm_sent', 'Creada'],
                $recent->map(fn ($n) => [
                    $n->id,
                    $n->type,
                    \Illuminate\Support\Str::limit($n->title, 40),
                    $n->fcm_sent ? 'sí' : 'no',
                    $n->created_at?->format('Y-m-d H:i:s'),
                ])->toArray()
            );
        }

        $this->newLine();

        // 5. Envío de prueba (por defecto sí; usar --no-send para solo diagnóstico)
        if ($this->option('no-send')) {
            $this->comment('Diagnóstico finalizado (sin envío). Quita --no-send para enviar prueba.');

            return self::SUCCESS;
        }

        $this->line('<fg=cyan>── Envío de prueba ──</>');

        if (empty($activeTokens)) {
            $this->error('  No se puede enviar: no hay tokens activos.');

            return self::FAILURE;
        }

        $title = 'Prueba diagnóstico FCM';
        $message = 'Mensaje de prueba desde notification:debug — ' . now()->format('H:i:s');
        $data = ['action' => 'debug', 'user_id' => (string) $userId];

        $success = $fcmService->sendTestToUser($user, $title, $message, $data);
        $stats = $fcmService->getLastSendStats();

        if ($success) {
            $this->info("  ✅ Enviado: {$stats['success_count']} token(s) aceptados por FCM de {$stats['total_tokens']}.");
            $this->line('  Si el teléfono no muestra la notificación, revisa permisos, canal Android (romanocc_channel) y que la app use el mismo proyecto Firebase.');
        } else {
            $this->error("  ❌ Falló: 0 de {$stats['total_tokens']} token(s) aceptados.");
            if ($stats['failure_count'] > 0) {
                $this->line("  Tokens con error: {$stats['failure_count']}");
            }
            if ($error = $fcmService->getLastError()) {
                $this->line('  Último error FCM: ' . $error);
            }
            if ($error !== null && str_contains(strtolower($error), 'senderid mismatch')) {
                $this->newLine();
                $this->warn('  El backend ya usa romanocc-4114f; este token se creó con otra config de la app (p. ej. proyecto 15e21).');
                $this->line('  1. Rebuild de la app con google-services.json de romanocc-4114f (project_number 410344054141).');
                $this->line('  2. Cerrar sesión en el teléfono e iniciar sesión de nuevo.');
                $this->line("  3. php artisan notification:reset-fcm-tokens {$userId}");
            }
            $this->line('  Revisa storage/logs/laravel.log para detalle por token.');

            return self::FAILURE;
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
