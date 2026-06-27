<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FcmService
{
    private $messaging;

    /** Último error para que el llamador pueda registrarlo */
    private ?string $lastError = null;

    private int $lastSuccessCount = 0;

    private int $lastFailureCount = 0;

    private int $lastTotalTokens = 0;

    public function __construct()
    {
        $this->messaging = Firebase::messaging();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastSendStats(): array
    {
        return [
            'success_count' => $this->lastSuccessCount,
            'failure_count' => $this->lastFailureCount,
            'total_tokens' => $this->lastTotalTokens,
        ];
    }

    /**
     * Comprueba que Firebase Messaging esté accesible (credenciales y proyecto).
     *
     * @throws \RuntimeException si la configuración falla
     */
    public function testConnection(): void
    {
        try {
            Firebase::messaging();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Firebase no está configurado correctamente: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Envía notificación de prueba a un usuario (sin persistir en BD).
     */
    public function sendTestToUser(User $user, string $title, string $message, array $data = []): bool
    {
        return $this->sendToUser($user, [
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send notification to specific user
     */
    public function sendToUser(User $user, array $notificationData): bool
    {
        $tokens = $user->getActiveFcmTokens();
        
        if (empty($tokens)) {
            Log::info('No active FCM tokens found for user: ' . $user->id);
            return false;
        }

        return $this->sendToTokens($tokens, $notificationData);
    }

    /**
     * Send notification to specific FCM tokens with simple configuration
     */
    public function sendToTokens(array $tokens, array $notificationData): bool
    {
        $this->lastError = null;
        $this->lastSuccessCount = 0;
        $this->lastFailureCount = 0;
        $this->lastTotalTokens = count($tokens);

        if (empty($tokens)) {
            return false;
        }

        try {
            $successCount = 0;
            $failureCount = 0;
            $data = array_merge($notificationData['data'] ?? [], ['click_action' => 'FLUTTER_NOTIFICATION_CLICK']);

            foreach ($tokens as $token) {
                try {
                    // Create a message with configuration to show as system notification even in foreground
                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification(FirebaseNotification::create(
                            $notificationData['title'] ?? 'ROMANOCC',
                            $notificationData['message'] ?? ''
                        ))
                        ->withData($data)
                        ->withAndroidConfig([
                            'notification' => [
                                'channel_id' => 'romanocc_channel',
                                'sound' => 'default',
                                'default_sound' => true,
                                'default_vibrate_timings' => true,
                                'visibility' => 'public',
                                'icon' => 'ic_notification',
                                'color' => '#E87700',
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            ],
                            'priority' => 'high',
                            'collapse_key' => 'romanocc_notification',
                        ])
                        ->withApnsConfig([
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                    'badge' => 1,
                                    'content-available' => 1,
                                    'alert' => [
                                        'title' => $notificationData['title'] ?? 'ROMANOCC',
                                        'body' => $notificationData['message'] ?? '',
                                    ],
                                ],
                            ],
                        ]);

                    $this->messaging->send($message);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $this->lastError = $e->getMessage();
                    $errorMessage = $e->getMessage();
                    $backendProjectId = $this->resolveBackendProjectId();
                    $context = [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $errorMessage,
                        'firebase_project_id' => $backendProjectId,
                    ];

                    if ($this->isInvalidFcmTokenError($errorMessage)) {
                        $deactivated = UserFcmToken::deactivateByToken($token);
                        $context['token_deactivated'] = $deactivated > 0;
                        $context['hint'] = 'Token obsoleto o de otro proyecto Firebase (p. ej. generado antes de migrar a '
                            . ($backendProjectId ?? 'este proyecto')
                            . '). Rebuild de la app con el google-services.json correcto, cerrar sesión, iniciar sesión '
                            . 'y volver a llamar POST /api/user/fcm-token.';
                    }

                    Log::error('FCM token error', $context);
                    $failureCount++;
                }
            }

            $this->lastSuccessCount = $successCount;
            $this->lastFailureCount = $failureCount;

            Log::info('FCM notification sent', [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_tokens' => count($tokens),
            ]);

            return $successCount > 0;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error('FCM service error', [
                'message' => $e->getMessage(),
                'tokens_count' => count($tokens)
            ]);
            return false;
        }
    }

    /**
     * project_id del JSON de credenciales en el servidor (para logs de diagnóstico).
     */
    /**
     * Errores FCM que no se corrigen reintentando el mismo token.
     */
    private function isInvalidFcmTokenError(string $message): bool
    {
        $normalized = strtolower($message);

        $needles = [
            'senderid mismatch',
            'notregistered',
            'not registered',
            'invalidregistration',
            'invalid registration',
            'unregistered',
            'registration token is not a valid',
            'requested entity was not found',
        ];

        foreach ($needles as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function resolveBackendProjectId(): ?string
    {
        $path = storage_path('app/firebase/service-account-key.json');
        if (! is_file($path)) {
            return env('FIREBASE_PROJECT_ID') ?: env('FIREBASE_PROJECT');
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) && ! empty($decoded['project_id'])
            ? (string) $decoded['project_id']
            : null;
    }
}
