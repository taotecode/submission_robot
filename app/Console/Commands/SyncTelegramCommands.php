<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class SyncTelegramCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync_tg:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $telegram = new Api('');

        $commands = collect($telegram->getCommands())->map(function ($command) {
            return $command->getName();
        })->toArray();

        $telegram->setMyCommands(compact('commands'));

        $this->info('Commands have been synchronized with Telegram Bot!');

        return Command::SUCCESS;
    }
}
