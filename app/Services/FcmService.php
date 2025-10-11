<?php

namespace App\Services;

use App\Models\User;
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
        if (empty($tokens)) {
            return false;
        }

        try {
            $successCount = 0;
            $failureCount = 0;

            foreach ($tokens as $token) {
                try {
                    // Create a message with configuration to show as system notification even in foreground
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
}
