<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendTermsNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-terms {type : terms or privacy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to all users about terms or privacy policy updates';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        if (!in_array($type, ['terms', 'privacy'])) {
            $this->error('Type must be either "terms" or "privacy"');
            return 1;
        }

        $this->info("Sending {$type} update notifications to all users...");

        try {
            if ($type === 'terms') {
                $this->notificationService->sendTermsUpdatedNotification();
                $this->info('Terms and conditions update notifications sent successfully!');
            } else {
                $this->notificationService->sendPrivacyUpdatedNotification();
                $this->info('Privacy policy update notifications sent successfully!');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error sending notifications: " . $e->getMessage());
            return 1;
        }
    }
}
