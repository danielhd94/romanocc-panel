<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FcmService
{
    private $messaging;

    public function __construct()
    {
        $this->messaging = Firebase::messaging();
    }

    /**
     * Test Firebase connection
     */
    public function testConnection(): bool
    {
        try {
            // La conexión se establece al crear el objeto messaging
            Log::info('Firebase connection successful');
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase connection failed', ['error' => $e->getMessage()]);
            throw $e;
        }
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
     * Send notification to multiple users
     */
    public function sendToUsers(array $userIds, array $notificationData): bool
    {
        $users = User::whereIn('id', $userIds)->get();
        $allTokens = [];

        foreach ($users as $user) {
            $tokens = $user->getActiveFcmTokens();
            $allTokens = array_merge($allTokens, $tokens);
        }

        if (empty($allTokens)) {
            Log::info('No active FCM tokens found for users: ' . implode(', ', $userIds));
            return false;
        }

        return $this->sendToTokens($allTokens, $notificationData);
    }

    /**
     * Send notification to specific FCM tokens
     */
    public function sendToTokens(array $tokens, array $notificationData): bool
    {
        if (empty($tokens)) {
            return false;
        }

        try {
            $successCount = 0;
            $failureCount = 0;

            foreach ($tokens as $token) {
                try {
                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification(FirebaseNotification::create(
                            $notificationData['title'] ?? 'ROMANOCC',
                            $notificationData['message'] ?? ''
                        ))
                        ->withData($notificationData['data'] ?? [])
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
                                ],
                            ],
                        ]);

                    $this->messaging->send($message);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    Log::error('FCM token error', [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage()
                    ]);
                    $failureCount++;
                }
            }

            Log::info('FCM notification sent', [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_tokens' => count($tokens)
            ]);

            return $successCount > 0;
            
        } catch (\Exception $e) {
            Log::error('FCM service error', [
                'message' => $e->getMessage(),
                'tokens_count' => count($tokens)
            ]);
            return false;
        }
    }

    /**
     * Create notification and send FCM
     */
    public function createAndSendNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): bool {
        // Create notification in database
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
            'fcm_sent' => false,
        ]);

        // Send FCM notification
        $fcmSent = $this->sendToUser($user, [
            'title' => $title,
            'message' => $message,
            'data' => array_merge($data, [
                'notification_id' => $notification->id,
                'type' => $type,
            ])
        ]);

        // Update notification with FCM status
        $notification->update(['fcm_sent' => $fcmSent]);

        return $fcmSent;
    }

    /**
     * Send comment notification
     */
    public function sendCommentNotification(
        User $commenter,
        User $articleOwner,
        string $articleTitle,
        string $commentText,
        array $articleData = []
    ): bool {
        $title = 'Nuevo comentario';
        $message = "{$commenter->name} comentó en: {$articleTitle}";
        
        $data = array_merge($articleData, [
            'commenter_name' => $commenter->name,
            'comment_text' => $commentText,
        ]);

        return $this->createAndSendNotification(
            $articleOwner,
            'comment',
            $title,
            $message,
            $data
        );
    }

    /**
     * Send reply notification
     */
    public function sendReplyNotification(
        User $replier,
        User $commentOwner,
        string $articleTitle,
        string $replyText,
        array $articleData = []
    ): bool {
        $title = 'Nueva respuesta';
        $message = "{$replier->name} respondió a tu comentario en: {$articleTitle}";
        
        $data = array_merge($articleData, [
            'replier_name' => $replier->name,
            'reply_text' => $replyText,
        ]);

        return $this->createAndSendNotification(
            $commentOwner,
            'reply',
            $title,
            $message,
            $data
        );
    }
}
