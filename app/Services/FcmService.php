<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send notification to a specific user
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'No FCM tokens found for user',
            ];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'No tokens provided',
            ];
        }

        try {
            $notification = Notification::create($title, $body);

            $results = [
                'success' => 0,
                'failed' => 0,
                'invalid_tokens' => [],
            ];

            foreach ($tokens as $token) {
                try {
                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData($data);

                    $this->messaging->send($message);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['invalid_tokens'][] = $token;

                    // Delete invalid tokens
                    if (str_contains($e->getMessage(), 'not a valid FCM registration token')) {
                        FcmToken::where('token', $token)->delete();
                    }
                }
            }

            return [
                'success' => true,
                'results' => $results,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent to topic successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Subscribe token to a topic
     */
    public function subscribeToTopic($tokens, string $topic): array
    {
        try {
            $tokens = is_array($tokens) ? $tokens : [$tokens];
            $this->messaging->subscribeToTopic($topic, $tokens);

            return [
                'success' => true,
                'message' => 'Subscribed to topic successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Unsubscribe token from a topic
     */
    public function unsubscribeFromTopic($tokens, string $topic): array
    {
        try {
            $tokens = is_array($tokens) ? $tokens : [$tokens];
            $this->messaging->unsubscribeFromTopic($topic, $tokens);

            return [
                'success' => true,
                'message' => 'Unsubscribed from topic successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
