<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserFcmToken;
use Illuminate\Console\Command;

class ResetUserFcmTokens extends Command
{
    protected $signature = 'notification:reset-fcm-tokens
                            {user_id : ID del usuario}
                            {--delete : Borrar filas en lugar de solo desactivar}';

    protected $description = 'Desactiva o elimina tokens FCM de un usuario para forzar registro nuevo desde la app';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $user = User::find($userId);

        if ($user === null) {
            $this->error("Usuario {$userId} no encontrado.");

            return self::FAILURE;
        }

        $query = UserFcmToken::where('user_id', $userId);
        $count = (clone $query)->count();

        if ($count === 0) {
            $this->warn("El usuario «{$user->name}» no tiene tokens FCM guardados.");

            return self::SUCCESS;
        }

        if ($this->option('delete')) {
            $query->delete();
            $this->info("Eliminados {$count} token(s) FCM del usuario {$userId}.");
        } else {
            UserFcmToken::deactivateUserTokens($userId);
            $this->info("Desactivados {$count} token(s) FCM del usuario {$userId}.");
        }

        $this->line('La app debe iniciar sesión de nuevo y llamar POST /api/user/fcm-token con un token nuevo.');

        return self::SUCCESS;
    }
}
