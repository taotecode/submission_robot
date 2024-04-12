<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;

class GetUpdateController extends Controller
{
    public function index()
    {
        $startService= new \App\Services\StartService();
        $botInfo = (new \App\Models\Bot())->with('review_group')->find(1);
        try {
            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
            $telegram->addCommands([
                \App\Telegram\Commands\StartCommand::class,
                \App\Telegram\Commands\GetGroupIdCommand::class,
                \App\Telegram\Commands\GetMeIdCommand::class,
                \App\Telegram\Commands\HelpCommand::class,
                \App\Telegram\Commands\BlackCommand::class,
                \App\Telegram\Commands\WhoCommand::class,
                \App\Telegram\Commands\ListCommand::class,
                \App\Telegram\Commands\SearchCommand::class,
            ]);
            $response = $telegram->commandsHandler(false);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        foreach ($response as $update) {
            $startService->index($botInfo,$update,$telegram);
        }

        dd($response);
    }
}
