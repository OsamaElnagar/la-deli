<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\TestFcmNotification;

class SendTestFcmNotification extends Command
{
    protected $signature = 'fcm:test {token}';
    protected $description = 'Send a test FCM notification to a given token';

    public function handle()
    {
        $token = $this->argument('token');
        $user = new User();
        $user->fcm_token = $token;
        $user->notify(new TestFcmNotification());
        $this->info('Test FCM notification sent to token: ' . $token);
    }
}
