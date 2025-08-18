<?php

namespace App\Console\Commands;

use App\Models\ChatControll;
use Illuminate\Console\Command;

class AutoReplayStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-replay-status-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ChatControll::where('auto_reply', 0)
            ->where('updated_at', '<=', now()->subDay())
            ->update(['auto_reply' => 1]);
    }
}
