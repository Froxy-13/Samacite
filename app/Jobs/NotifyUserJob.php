<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyUserJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour
    public int $tries = 3;

    public function __construct(
        public User $user,
        public string $message,
        public ?array $data = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        try {
            // Check if user exists
            if (!$this->user->exists) {
                Log::warning('NotifyUserJob: User not found', [
                    'user_id' => $this->user->id,
                ]);
                return false;
            }

            // Create a notification entry in the database
            $this->user->notify(
                new \App\Notifications\GenericNotification(
                    $this->message,
                    $this->data ?? []
                )
            );

            Log::info('NotifyUserJob: User notified successfully', [
                'user_id' => $this->user->id,
                'message' => $this->message,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('NotifyUserJob: Error notifying user', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyUserJob job failed', [
            'user_id' => $this->user->id,
            'message' => $this->message,
            'error' => $exception->getMessage(),
        ]);
    }
}
