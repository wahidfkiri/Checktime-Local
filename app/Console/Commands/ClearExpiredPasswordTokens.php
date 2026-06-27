<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearExpiredPasswordTokens extends Command
{
    protected $signature = 'tokens:clear-expired';
    protected $description = 'Clear expired password reset tokens';

    public function handle()
    {
        $expired = Carbon::now()->subMinutes(config('auth.passwords.users.expire', 60));
        
        $deleted = DB::table('password_reset_tokens')
            ->where('created_at', '<', $expired)
            ->delete();
            
        $this->info("Cleared {$deleted} expired password reset tokens.");
        
        return 0;
    }
}