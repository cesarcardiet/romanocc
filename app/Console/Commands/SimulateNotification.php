<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\InformationApp;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SimulateNotification extends Command
{
    protected $signature = 'notification:simulate
                            {type : terms|article|privacy — igual que al guardar en Filament}
                            {--user= : ID del usuario destino (recomendado en pruebas)}
                            {--article= : ID del artículo (obligatorio si type=article)}
                            {--url= : URL del PDF para terms/privacy (por defecto la de InformationApp)}
                            {--all : Enviar a todos los usuarios, como en producción}';

    protected $description = 'Emula notificaciones de términos actualizados o artículo/ley actualizado (mismo flujo que el panel Filament)';

    public function handle(NotificationService $notificationService, FcmService $fcmService): int
    {
        $type = $this->argument('type');

        if (! in_array($type, ['terms', 'article', 'privacy'], true)) {
            $this->error('type debe ser: terms, article o privacy');

            return self::FAILURE;
        }

        try {
            $fcmService->testConnection();
        } catch (\Throwable $e) {
            $this->error('Firebase no configurado: ' . $e->getMessage());

            return self::FAILURE;
        }

        $onlyUserId = $this->resolveTargetUserId();
        if ($onlyUserId === false) {
            return self::FAILURE;
        }

        $beforeMaxId = (int) (Notification::max('id') ?? 0);

        try {
            match ($type) {
                'terms' => $this->simulateTerms($notificationService, $onlyUserId),
                'privacy' => $this->simulatePrivacy($notificationService, $onlyUserId),
                'article' => $this->simulateArticle($notificationService, $onlyUserId),
            };
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $created = Notification::query()
            ->where('id', '>', $beforeMaxId)
            ->get();

        $fcmSent = $created->where('fcm_sent', true)->count();
        $total = $created->count();

        $this->newLine();
        $this->info("Simulación «{$type}» completada.");
        $this->line("  Notificaciones en BD: {$total}");
        $this->line("  FCM entregado (fcm_sent=true): {$fcmSent}");

        if ($total > 0 && $fcmSent === 0) {
            $this->warn('  Ningún push llegó a FCM. Revisa tokens activos (notification:debug).');
        }

        if ($created->isNotEmpty()) {
            $last = $created->last();
            $this->line('  Última notificación:');
            $this->line("    type: {$last->type}");
            $this->line("    title: {$last->title}");
            $this->line('    data: ' . json_encode($last->data, JSON_UNESCAPED_UNICODE));
        }

        return $fcmSent > 0 || $total > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function resolveTargetUserId(): int|false|null
    {
        if ($this->option('all')) {
            if ($this->option('user')) {
                $this->error('No combines --all con --user.');

                return false;
            }

            if (! $this->confirm('¿Enviar a TODOS los usuarios (comportamiento de producción)?', false)) {
                $this->line('Cancelado.');

                return false;
            }

            return null;
        }

        $userId = $this->option('user');

        if ($userId !== null && $userId !== '') {
            return (int) $userId;
        }

        $users = User::query()->orderBy('name')->get();
        if ($users->isEmpty()) {
            $this->error('No hay usuarios en la base de datos.');

            return false;
        }

        $chosen = $this->choice(
            'Usuario destino (misma notificación que en producción, solo para él):',
            $users->pluck('name', 'id')->toArray()
        );

        return (int) $chosen;
    }

    private function simulateTerms(NotificationService $notificationService, ?int $onlyUserId): void
    {
        $info = InformationApp::query()->first();
        $url = $this->option('url') ?: $info?->url_terminos_y_condiciones;

        $this->line('<fg=cyan>── Términos y condiciones (EditInformationApp) ──</>');
        $this->line('  Método: sendTermsUpdatedNotification(url)');
        $this->line('  action: view_terms');
        if ($url) {
            $this->line('  url: ' . $url);
        }
        if ($onlyUserId) {
            $this->line("  Destino: usuario #{$onlyUserId}");
        } else {
            $this->line('  Destino: todos los usuarios');
        }

        $notificationService->sendTermsUpdatedNotification($url ?: null, $onlyUserId);
    }

    private function simulatePrivacy(NotificationService $notificationService, ?int $onlyUserId): void
    {
        $info = InformationApp::query()->first();
        $url = $this->option('url') ?: $info?->url_politica_de_privacidad;

        $this->line('<fg=cyan>── Política de privacidad (EditInformationApp) ──</>');
        $this->line('  Método: sendPrivacyUpdatedNotification(url)');
        $this->line('  action: view_privacy');
        if ($url) {
            $this->line('  url: ' . $url);
        }
        if ($onlyUserId) {
            $this->line("  Destino: usuario #{$onlyUserId}");
        } else {
            $this->line('  Destino: todos los usuarios');
        }

        $notificationService->sendPrivacyUpdatedNotification($url ?: null, $onlyUserId);
    }

    private function simulateArticle(NotificationService $notificationService, ?int $onlyUserId): void
    {
        $articleId = $this->option('article');
        if ($articleId === null || $articleId === '') {
            $this->error('Para type=article indica --article=ID (artículo existente en la BD).');

            throw new \InvalidArgumentException('Falta --article');
        }

        $article = Article::with('law')->find($articleId);
        if ($article === null) {
            throw new \InvalidArgumentException("Artículo #{$articleId} no encontrado.");
        }

        $this->line('<fg=cyan>── Artículo actualizado (EditArticle) ──</>');
        $this->line('  Método: sendArticleUpdatedNotification(article)');
        $this->line('  action: view_articles');
        $this->line('  Artículo: ' . $article->article_title);
        if ($article->law) {
            $this->line('  Ley: ' . $article->law->name . ' (' . $article->law->type . ')');
        }
        if ($onlyUserId) {
            $this->line("  Destino: usuario #{$onlyUserId} (con token FCM activo)");
        } else {
            $this->line('  Destino: usuarios con token FCM activo');
        }

        if ($onlyUserId) {
            $user = User::find($onlyUserId);
            if ($user && empty($user->getActiveFcmTokens())) {
                $this->warn('  Este usuario no tiene tokens FCM activos; se creará la notificación en BD pero FCM puede fallar.');
            }
        }

        $notificationService->sendArticleUpdatedNotification($article, $onlyUserId);
    }
}
