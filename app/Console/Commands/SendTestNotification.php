<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

class SendTestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to a user';

    /**
     * Execute the console command.
     */
    public function handle(FcmService $fcmService)
    {
        // Verificar que Firebase esté configurado
        try {
            $fcmService->testConnection();
        } catch (\Exception $e) {
            $this->error('Error de configuración de Firebase: ' . $e->getMessage());
            return 1;
        }

        $userId = $this->argument('user_id');
        
        if (!$userId) {
            $users = User::all();
            if ($users->isEmpty()) {
                $this->error('No users found in the database.');
                return 1;
            }
            
            $userChoices = $users->pluck('name', 'id')->toArray();
            $userId = $this->choice('Select a user to send notification to:', $userChoices);
        }

        $user = User::find($userId);
        
        if (!$user) {
            $this->error('User not found.');
            return 1;
        }

        $this->info("Sending test notification to: {$user->name}");

        $success = $fcmService->createAndSendNotification(
            $user,
            'test',
            'Notificación de Prueba',
            'Esta es una notificación de prueba desde ROMANOCC',
            [
                'test_data' => 'Hello from backend!',
                'timestamp' => now()->toISOString(),
            ]
        );

        if ($success) {
            $this->info('✅ Test notification sent successfully!');
        } else {
            $this->error('❌ Failed to send test notification.');
        }

        return 0;
    }
}
