<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\UserFcmToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
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
    public function sendTermsUpdatedNotification()
    {
        // Notificar a todos los usuarios
        $users = User::all();
        
        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_TERMS_UPDATED,
                'Términos y condiciones actualizados',
                'Los términos y condiciones han sido actualizados. Te recomendamos revisarlos.',
                [
                    'action' => 'view_terms'
                ]
            );
        }
    }

    /**
     * Enviar notificación de actualización de políticas de privacidad
     */
    public function sendPrivacyUpdatedNotification()
    {
        // Notificar a todos los usuarios
        $users = User::all();
        
        foreach ($users as $user) {
            $this->createAndSendNotification(
                $user->id,
                self::TYPE_PRIVACY_UPDATED,
                'Políticas de privacidad actualizadas',
                'Las políticas de privacidad han sido actualizadas. Te recomendamos revisarlas.',
                [
                    'action' => 'view_privacy'
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

            // Enviar notificación push
            $this->sendPushNotification($userId, $title, $message, $data);

            // Marcar como enviada
            $notification->markAsSent();

            Log::info("Notificación enviada", [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title
            ]);

        } catch (\Exception $e) {
            Log::error("Error enviando notificación", [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar notificación push via FCM
     */
    private function sendPushNotification($userId, $title, $message, $data = [])
    {
        try {
            $fcmTokens = UserFcmToken::getActiveTokens($userId);
            
            if (empty($fcmTokens)) {
                Log::info("No hay tokens FCM activos para el usuario", ['user_id' => $userId]);
                return;
            }

            $serverKey = config('services.fcm.server_key');
            
            if (!$serverKey) {
                Log::error("FCM Server Key no configurada");
                return;
            }

            $notificationData = [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
                'badge' => 1,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'data' => array_merge($data, [
                    'notification_type' => $data['action'] ?? 'general',
                    'timestamp' => now()->timestamp
                ])
            ];

            foreach ($fcmTokens as $token) {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'notification' => $notificationData,
                    'data' => $notificationData['data'],
                    'priority' => 'high',
                    'android' => [
                        'notification' => [
                            'channel_id' => 'romanocc_channel',
                            'priority' => 'high',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    Log::info("Notificación push enviada exitosamente", [
                        'user_id' => $userId,
                        'token' => substr($token, 0, 20) . '...'
                    ]);
                } else {
                    Log::error("Error enviando notificación push", [
                        'user_id' => $userId,
                        'response' => $response->body()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error en sendPushNotification", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
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
        
        Log::info("Notificaciones antiguas eliminadas", ['count' => $deleted]);
        
        return $deleted;
    }
}
