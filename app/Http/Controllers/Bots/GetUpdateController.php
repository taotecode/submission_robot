<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;

class GetUpdateController extends Controller
{
    public function index($id)
    {
        $startService = new \App\Services\StartService();
        $callBackQueryService = new \App\Services\CallBackQueryService();
        $botInfo = (new \App\Models\Bot())->with('review_group')->find(2);
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
                \App\Telegram\Commands\SettingsCommand::class,
            ]);
            $response = $telegram->commandsHandler(false);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        foreach ($response as $updateData) {
            dump($updateData);
            if ($updateData->hasCommand()&&$updateData->objectType()!=='callback_query'){
                continue;
            }
            if ($updateData->objectType() === 'callback_query') {
                $callBackQueryService->index($botInfo, $updateData, $telegram);
            }elseif (
                $updateData->getChat()->type === 'private'
            ) {
                return $startService->index($botInfo, $updateData, $telegram);
            }
        }

        dd($response);
    }
}
