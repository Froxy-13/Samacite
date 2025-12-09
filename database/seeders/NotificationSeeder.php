<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin users
        $admins = User::role('Admin')->get();
        
        if ($admins->isEmpty()) {
            $this->command->warn('No admin users found.');
            return;
        }

        // Get recent reports
        $reports = Report::latest()->take(4)->get();

        if ($reports->isEmpty()) {
            $this->command->warn('No reports found.');
            return;
        }

        $adminUser = $admins->first();

        // Create notifications for each report
        foreach ($reports as $index => $report) {
            $notifications = [
                [
                    'title' => 'Nouveau signalement créé',
                    'message' => "Un nouveau signalement a été créé: \"{$report->title}\"",
                    'type' => 'report_created',
                    'reportId' => $report->id,
                ],
                [
                    'title' => 'Signalement modifié',
                    'message' => "Le signalement \"{$report->title}\" a été modifié",
                    'type' => 'report_updated',
                    'reportId' => $report->id,
                ],
                [
                    'title' => 'Nouveau commentaire',
                    'message' => "Un nouveau commentaire sur \"{$report->title}\"",
                    'type' => 'comment_added',
                    'reportId' => $report->id,
                ],
            ];

            foreach ($notifications as $notification) {
                $notificationData = json_encode([
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'type' => $notification['type'],
                    'report_id' => $notification['reportId'],
                    'url' => "/reports/{$notification['reportId']}",
                ]);
                
                DatabaseNotification::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'notifiable_id' => $adminUser->id,
                    'notifiable_type' => 'App\\Models\\User',
                    'type' => 'App\\Notifications\\GenericNotification',
                    'data' => $notificationData,
                    'read_at' => $index === 0 ? now() : null,
                ]);
            }
        }

        $totalNotifications = $reports->count() * 3;
        $this->command->info("✅ {$totalNotifications} notifications créées pour les admins");
        $this->command->info('🔔 Chaque notification inclut le report_id pour la redirection');
    }
}
