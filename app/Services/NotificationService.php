<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\UserFcmToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Tipos de notificaciones disponibles
     */
    const TYPE_FORUM_TOPIC_COMMENT = 'forum_topic_comment';
    const TYPE_FORUM_COMMENT_REPLY = 'forum_comment_reply';
    const TYPE_ARTICLE_COMMENT = 'article_comment';
    const TYPE_ARTICLE_COMMENT_REPLY = 'article_comment_reply';
    const TYPE_TERMS_UPDATED = 'terms_updated';
    const TYPE_PRIVACY_UPDATED = 'privacy_updated';
    const TYPE_ARTICLE_OPINION_CREATED = 'article_opinion_created';
    const TYPE_ARTICLE_OPINION_UPDATED = 'article_opinion_updated';
    const TYPE_ARTICLE_RESOLUTION_CREATED = 'article_resolution_created';
    const TYPE_ARTICLE_RESOLUTION_UPDATED = 'article_resolution_updated';
    const TYPE_ARTICLE_VIDEO_CREATED = 'article_video_created';
    const TYPE_ARTICLE_VIDEO_UPDATED = 'article_video_updated';
    const TYPE_ARTICLE_UPDATED = 'article_updated';
    const TYPE_CHAPTER_UPDATED = 'chapter_updated';
    const TYPE_TITLE_UPDATED = 'title_updated';

    /**
     * Enviar notificación de comentario en tema del foro
     */
    public function sendForumTopicCommentNotification($topicOwnerId, $commenterName, $topicTitle, $commentText, $topicId = null)
    {
        if ($topicOwnerId == auth()->id()) {
            return; // No notificar al propio autor del tema
        }

        $this->createAndSendNotification(
            $topicOwnerId,
            self::TYPE_FORUM_TOPIC_COMMENT,
            'Nuevo comentario en tu tema',
            "{$commenterName} comentó en tu tema: \"{$topicTitle}\"",
            [
                'topic_title' => $topicTitle,
                'topic_id' => $topicId,
                'comment_text' => $this->truncateText($commentText, 100),
                'commenter_name' => $commenterName,
                'action' => 'view_forum_topic'
            ]
        );
    }

    /**
     * Enviar notificación de respuesta a comentario del foro
     */
    public function sendForumCommentReplyNotification($commentOwnerId, $replierName, $topicTitle, $replyText, $topicId = null)
    {
        if ($commentOwnerId == auth()->id()) {
            return; // No notificar al propio autor del comentario
        }

        $this->createAndSendNotification(
            $commentOwnerId,
            self::TYPE_FORUM_COMMENT_REPLY,
            'Respuesta a tu comentario',
            "{$replierName} respondió a tu comentario en: \"{$topicTitle}\"",
            [
                'topic_title' => $topicTitle,
                'topic_id' => $topicId,
                'reply_text' => $this->truncateText($replyText, 100),
                'replier_name' => $replierName,
                'action' => 'view_forum_topic'
            ]
        );
    }

    /**
     * Enviar notificación de comentario en artículo
     */
    public function sendArticleCommentNotification($articleOwnerId, $commenterName, $articleTitle, $commentText, $articleId = null, $lawId = null)
    {
        if ($articleOwnerId == auth()->id()) {
            return; // No notificar al propio autor del artículo
        }

        $this->createAndSendNotification(
            $articleOwnerId,
            self::TYPE_ARTICLE_COMMENT,
            'Nuevo comentario en artículo',
            "{$commenterName} comentó en el artículo: \"{$articleTitle}\"",
            [
                'article_title' => $articleTitle,
                'article_id' => $articleId,
                'law_id' => $lawId,
                'comment_text' => $this->truncateText($commentText, 100),
                'commenter_name' => $commenterName,
                'action' => 'view_article'
            ]
        );
    }

    /**
     * Enviar notificación de respuesta a comentario de artículo
     */
    public function sendArticleCommentReplyNotification($commentOwnerId, $replierName, $articleTitle, $replyText, $articleId = null, $lawId = null)
    {
        if ($commentOwnerId == auth()->id()) {
            return; // No notificar al comentario
        }

        $this->createAndSendNotification(
            $commentOwnerId,
            self::TYPE_ARTICLE_COMMENT_REPLY,
            'Respuesta a tu comentario',
            "{$replierName} respondió a tu comentario en: \"{$articleTitle}\"",
            [
                'article_title' => $articleTitle,
                'article_id' => $articleId,
                'law_id' => $lawId,
                'reply_text' => $this->truncateText($replyText, 100),
                'replier_name' => $replierName,
                'action' => 'view_article'
            ]
        );
    }

    /**
     * Enviar notificación de actualización de términos y condiciones
     */
    public function sendTermsUpdatedNotification($termsUrl = null, ?int $onlyUserId = null)
    {
        $users = $this->resolveNotificationRecipients($onlyUserId, requireActiveFcm: false);

        foreach ($users as $user) {
            $data = [
                'action' => 'view_terms'
            ];

            // Agregar URL del PDF si se proporciona
            if ($termsUrl) {
                $data['url'] = $termsUrl;
            }

            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_UPDATED,
                'Términos y condiciones actualizados',
                'Los términos y condiciones han sido actualizados. Te recomendamos revisarlos.',
                $data
            );
        }
    }

    /**
     * Enviar notificación de actualización de políticas de privacidad
     */
    public function sendPrivacyUpdatedNotification($privacyUrl = null, ?int $onlyUserId = null)
    {
        $users = $this->resolveNotificationRecipients($onlyUserId, requireActiveFcm: false);

        foreach ($users as $user) {
            $data = [
                'action' => 'view_privacy'
            ];

            // Agregar URL del PDF si se proporciona
            if ($privacyUrl) {
                $data['url'] = $privacyUrl;
            }

            $this->createAndSendNotification(
                $user->id,
                self::TYPE_PRIVACY_UPDATED,
                'Políticas de privacidad actualizadas',
                'Las políticas de privacidad han sido actualizadas. Te recomendamos revisarlas.',
                $data
            );
        }
    }

    /**
     * Enviar notificación cuando se crea una opinión de artículo
     */
    public function sendArticleOpinionCreatedNotification($articleOpinion)
    {
        // Notificar a todos los usuarios sobre la nueva opinión
        $users = User::all();
        $articleTitle = $articleOpinion->article ? $articleOpinion->article->article_title : 'Artículo';
        $law = $articleOpinion->article && $articleOpinion->article->law ? $articleOpinion->article->law : null;
        $lawName = $law ? $law->name : '';
        $lawTypeText = $this->getLawTypeText($law);
        $opinionText = strip_tags($articleOpinion->opinion);
        $truncatedOpinion = $this->truncateText($opinionText, 100);

        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_OPINION_CREATED,
                'Nueva adición publicada',
                "Se ha publicado una nueva adición en el artículo {$lawTypeText}: \"{$articleTitle}\"",
                [
                    'article_title' => $articleTitle,
                    'article_id' => $articleOpinion->article_id,
                    'law_id' => $articleOpinion->article ? $articleOpinion->article->law_id : null,
                    'law_name' => $lawName,
                    'opinion_text' => $truncatedOpinion,
                    'opinion_id' => $articleOpinion->id,
                    'action' => 'view_article_opinion'
                ]
            );
        }
    }

    /**
     * Enviar notificación cuando se actualiza una opinión de artículo
     */
    public function sendArticleOpinionUpdatedNotification($articleOpinion)
    {
        // Notificar a todos los usuarios sobre la actualización de la opinión
        $users = User::all();
        $articleTitle = $articleOpinion->article ? $articleOpinion->article->article_title : 'Artículo';
        $law = $articleOpinion->article && $articleOpinion->article->law ? $articleOpinion->article->law : null;
        $lawName = $law ? $law->name : '';
        $lawTypeText = $this->getLawTypeText($law);
        $opinionText = strip_tags($articleOpinion->opinion);
        $truncatedOpinion = $this->truncateText($opinionText, 100);

        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_OPINION_UPDATED,
                'Adición actualizada',
                "Se ha actualizado una adición en el artículo {$lawTypeText}: \"{$articleTitle}\"",
                [
                    'article_title' => $articleTitle,
                    'article_id' => $articleOpinion->article_id,
                    'law_id' => $articleOpinion->article ? $articleOpinion->article->law_id : null,
                    'law_name' => $lawName,
                    'opinion_text' => $truncatedOpinion,
                    'opinion_id' => $articleOpinion->id,
                    'action' => 'view_article_opinion'
                ]
            );
        }
    }

    /**
     * Enviar notificación cuando se crea una resolución de artículo
     */
    public function sendArticleResolutionCreatedNotification($articleResolution)
    {
        // Notificar a todos los usuarios sobre la nueva resolución
        $users = User::all();
        $articleTitle = $articleResolution->article ? $articleResolution->article->article_title : 'Artículo';
        $law = $articleResolution->article && $articleResolution->article->law ? $articleResolution->article->law : null;
        $lawName = $law ? $law->name : '';
        $lawTypeText = $this->getLawTypeText($law);
        $resolutionName = $articleResolution->name ?? 'Resolución';

        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_RESOLUTION_CREATED,
                'Nueva resolución publicada',
                "Se ha publicado una nueva resolución \"{$resolutionName}\" en el artículo {$lawTypeText}: \"{$articleTitle}\"",
                [
                    'article_title' => $articleTitle,
                    'article_id' => $articleResolution->article_id,
                    'law_id' => $articleResolution->article ? $articleResolution->article->law_id : null,
                    'law_name' => $lawName,
                    'resolution_name' => $resolutionName,
                    'resolution_id' => $articleResolution->id,
                    'action' => 'view_article_resolution'
                ]
            );
        }
    }

    /**
     * Enviar notificación cuando se actualiza una resolución de artículo
     */
    public function sendArticleResolutionUpdatedNotification($articleResolution)
    {
        // Notificar a todos los usuarios sobre la actualización de la resolución
        $users = User::all();
        $articleTitle = $articleResolution->article ? $articleResolution->article->article_title : 'Artículo';
        $law = $articleResolution->article && $articleResolution->article->law ? $articleResolution->article->law : null;
        $lawName = $law ? $law->name : '';
        $lawTypeText = $this->getLawTypeText($law);
        $resolutionName = $articleResolution->name ?? 'Resolución';

        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_RESOLUTION_UPDATED,
                'Resolución actualizada',
                "Se ha actualizado la resolución \"{$resolutionName}\" en el artículo {$lawTypeText}: \"{$articleTitle}\"",
                [
                    'article_title' => $articleTitle,
                    'article_id' => $articleResolution->article_id,
                    'law_id' => $articleResolution->article ? $articleResolution->article->law_id : null,
                    'law_name' => $lawName,
                    'resolution_name' => $resolutionName,
                    'resolution_id' => $articleResolution->id,
                    'action' => 'view_article_resolution'
                ]
            );
        }
    }

    /**
     * Enviar notificación cuando se crea un video de artículo
     */
    public function sendArticleVideoCreatedNotification($articleVideo)
    {
        // Notificar a todos los usuarios sobre la nueva opinión
        $users = User::all();
        $articleTitle = $articleVideo->article ? $articleVideo->article->article_title : 'Artículo';
        $law = $articleVideo->article && $articleVideo->article->law ? $articleVideo->article->law : null;
        $lawName = $law ? $law->name : '';
        $lawTypeText = $this->getLawTypeText($law);
        $opinionText = strip_tags($articleVideo->opinion);
        $truncatedOpinion = $this->truncateText($opinionText, 100);

        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_VIDEO_CREATED,
                'Nuevo video publicado',
                "Se ha publicado un nuevo video en el artículo {$lawTypeText}: \"{$articleTitle}\"",
                [
                    'article_title' => $articleTitle,
                    'article_id' => $articleVideo->article_id,
                    'law_id' => $articleVideo->article ? $articleVideo->article->law_id : null,
                    'law_name' => $lawName,
                    'opinion_text' => $truncatedOpinion,
                    'opinion_id' => $articleVideo->id,
                    'action' => 'view_article_opinion'
                ]
            );
        }
    }

    /**
     * Enviar notificación cuando se actualiza un video de artículo
     */
    public function sendArticleVideoUpdatedNotification($articleVideo)
    {
        // Notificar a todos los usuarios sobre la actualización del video
        $users = User::all();
        $articleTitle = $articleVideo->article ? $articleVideo->article->article_title : 'Artículo';
        $law = $articleVideo->article && $articleVideo->article->law ? $articleVideo->article->law : null;
        $lawName = $law ? $law->name : '';
        $lawTypeText = $this->getLawTypeText($law);
        $videoText = strip_tags($articleVideo->name ?? '');
        $truncatedVideo = $this->truncateText($videoText, 100);

        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_VIDEO_UPDATED,
                'Video actualizado',
                "Se ha actualizado un video en el artículo {$lawTypeText}: \"{$articleTitle}\"",
                [
                    'article_title' => $articleTitle,
                    'article_id' => $articleVideo->article_id,
                    'law_id' => $articleVideo->article ? $articleVideo->article->law_id : null,
                    'law_name' => $lawName,
                    'video_text' => $truncatedVideo,
                    'video_id' => $articleVideo->id,
                    'action' => 'view_article_opinion'
                ]
            );
        }
    }

    /**
     * Crear y enviar notificación
     */
    private function createAndSendNotification($userId, $type, $title, $message, $data = [])
    {
        try {
            // Crear notificación en la base de datos
            $notification = Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'is_read' => false,
                'fcm_sent' => false,
            ]);

            // Enviar notificación push; solo marcar fcm_sent si FCM aceptó al menos un envío
            $fcmSent = $this->sendPushNotification($userId, $title, $message, $data);

            if ($fcmSent) {
                $notification->markAsSent();
            }

            // Log::info("Notificación enviada", [
            //     'user_id' => $userId,
            //     'type' => $type,
            //     'title' => $title
            // ]);

        } catch (\Exception $e) {
            Log::error("Error enviando notificación", [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar notificación push via FCM.
     *
     * @return bool true si al menos un token recibió el mensaje en FCM
     */
    private function sendPushNotification($userId, $title, $message, $data = []): bool
    {
        try {
            $user = User::find($userId);

            if (! $user) {
                Log::warning('sendPushNotification: usuario no encontrado', ['user_id' => $userId]);

                return false;
            }

            $fcmTokens = UserFcmToken::getActiveTokens($userId);

            if (empty($fcmTokens)) {
                Log::info('sendPushNotification: sin tokens FCM activos', ['user_id' => $userId]);

                return false;
            }

            $fcmService = app(FcmService::class);

            $notificationData = [
                'title' => $title,
                'message' => $message,
                'data' => array_merge($data, [
                    'notification_type' => $data['action'] ?? 'general',
                    'timestamp' => (string) now()->timestamp,
                ]),
            ];

            $success = $fcmService->sendToUser($user, $notificationData);

            if (! $success) {
                Log::error('Error enviando notificación push via FcmService', [
                    'user_id' => $userId,
                    'title' => $title,
                    'fcm_error' => $fcmService->getLastError(),
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Error en sendPushNotification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Destinatarios para notificaciones masivas o simulación a un solo usuario.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function resolveNotificationRecipients(?int $onlyUserId, bool $requireActiveFcm)
    {
        if ($onlyUserId !== null) {
            $query = User::query()->where('id', $onlyUserId);
            if ($requireActiveFcm) {
                $query->whereHas('activeFcmTokens');
            }

            return $query->get();
        }

        $query = User::query();
        if ($requireActiveFcm) {
            $query->has('activeFcmTokens');
        }

        return $query->get();
    }

    /**
     * Obtener texto "de la ley" o "del reglamento" según el tipo
     */
    private function getLawTypeText($law)
    {
        if (!$law) {
            return 'de la ley';
        }
        
        return $law->type === 'reglamento' ? 'del reglamento' : 'de la ley';
    }

    /**
     * Truncar texto para notificaciones
     */
    private function truncateText($text, $length = 100)
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function getNotificationStats($userId)
    {
        $total = Notification::where('user_id', $userId)->count();
        $unread = Notification::where('user_id', $userId)->where('is_read', false)->count();
        
        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $total - $unread
        ];
    }

    /**
     * Limpiar notificaciones antiguas (más de 30 días)
     */
    public function cleanupOldNotifications()
    {
        $deleted = Notification::where('created_at', '<', now()->subDays(30))->delete();
        
        // Log::info("Notificaciones antiguas eliminadas", ['count' => $deleted]);
        
        return $deleted;
    }
    
    /**
     * Enviar notificación de actualización de artículos
     */
    public function sendArticleUpdatedNotification($article = null, ?int $onlyUserId = null)
    {
        if ($article === null) {
            throw new \InvalidArgumentException('Se requiere un artículo para la notificación de actualización.');
        }

        $users = $this->resolveNotificationRecipients($onlyUserId, requireActiveFcm: true);
        $law = $article && $article->law ? $article->law : null;
        $lawTypeText = $this->getLawTypeText($law);
        
        foreach ($users as $user) {
            $data = [
                'action' => 'view_articles'
            ];
            // Agregar URL del PDF si se proporciona
            if ($article->id) {
                $data['url'] = null;
            }
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_ARTICLE_UPDATED,
                'Artículo actualizado',
                "El artículo {$lawTypeText} \"{$article->article_title}\" ha sido actualizado. Ingresa para revisarlo.",
                $data
            );
        }
    }
    
    /**
     * Enviar notificación de actualización de capítulos
     */
    public function sendChapterUpdatedNotification($chapter = null)
    {
        // Notificar a todos los usuarios
        $users = User::whereHas('fcmTokens', function($query) {
            $query->where('is_active', true);
        })->get();
        $law = $chapter && $chapter->law ? $chapter->law : null;
        $lawTypeText = $this->getLawTypeText($law);
        
        foreach ($users as $user) {
            $data = [
                'action' => 'view_chapters'
            ];
            // Agregar URL del PDF si se proporciona
            if ($chapter->id) {
                $data['url'] = null;
            }
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_CHAPTER_UPDATED,
                'Capítulo actualizado',
                "El capítulo {$lawTypeText} \"{$chapter->chapter_title}\" ha sido actualizado. Ingresa para revisarlo.",
                $data
            );
        }
    }

    /**
     * Enviar notificación de actualización de subcapítulos
     */
    public function sendSubchapterUpdatedNotification($subchapter = null)
    {
        // Notificar a todos los usuarios
        $users = User::whereHas('fcmTokens', function($query) {
            $query->where('is_active', true);
        })->get();
        $law = $subchapter && $subchapter->law ? $subchapter->law : null;
        $lawTypeText = $this->getLawTypeText($law);
        
        // Obtener información del capítulo y título para el texto más largo
        $chapter = $subchapter && $subchapter->chapter ? $subchapter->chapter : null;
        $title = $chapter && $chapter->title ? $chapter->title : null;
        
        $subchapterNumber = $subchapter->subchapter_number ?? '';
        $subchapterTitle = $subchapter->subchapter_title ?? '';
        $chapterNumber = $chapter ? $chapter->chapter_number : '';
        $titleName = $title ? $title->title : 'TÍTULO';
        
        // Formato: "El subcapítulo I: Finalidad de la ley, del CAPITULO III, TITULO IV ha sido actualizado"
        $subchapterDisplay = $subchapterTitle;
        $chapterDisplay = $chapterNumber ? "CAPÍTULO {$chapterNumber}" : 'CAPÍTULO';
        
        foreach ($users as $user) {
            $data = [
                'action' => 'view_subchapters'
            ];
            // Agregar URL del PDF si se proporciona
            if ($subchapter->id) {
                $data['url'] = null;
            }
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_CHAPTER_UPDATED,
                'Subcapítulo actualizado',
                "El subcapítulo {$lawTypeText} \"{$subchapterDisplay}\", del {$chapterDisplay}, {$titleName} ha sido actualizado.",
                $data
            );
        }
    }

    /**
     * Enviar notificación de actualización de titulo
     */
    public function sendTitleUpdatedNotification($title = null)
    {
        // Notificar a todos los usuarios
        $users = User::whereHas('fcmTokens', function($query) {
            $query->where('is_active', true);
        })->get();
        $law = $title && $title->law ? $title->law : null;
        $lawTypeText = $this->getLawTypeText($law);
        
        foreach ($users as $user) {
            $data = [
                'action' => 'view_titles'
            ];
            // Agregar URL del PDF si se proporciona
            if ($title->id) {
                $data['url'] = null;
            }
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_TITLE_UPDATED,
                'Título actualizado',
                "El título {$lawTypeText} \"{$title->title}\" ha sido actualizado. Ingresa para revisarlo.",
                $data
            );
        }
    }
}
