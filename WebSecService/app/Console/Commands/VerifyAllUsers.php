<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerifyAllUsers extends Command
{
    protected $signature = 'users:verify-all';
    protected $description = 'Verify all existing users';

    public function handle()
    {
        $count = User::whereNull('email_verified_at')->count();
        
        if ($count === 0) {
            $this->info('No unverified users found.');
            return;
        }

        if ($this->confirm("This will verify {$count} users. Do you wish to continue?")) {
            User::whereNull('email_verified_at')
                ->update(['email_verified_at' => Carbon::now()]);
            
            $this->info("Successfully verified {$count} users.");
        }
    }
} 