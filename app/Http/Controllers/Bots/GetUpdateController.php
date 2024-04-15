<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;

class GetUpdateController extends Controller
{
    public function index()
    {
        $startService= new \App\Services\StartService();
        $callBackQueryService = new \App\Services\CallBackQueryService();
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

        foreach ($response as $updateData) {
            if ($updateData->objectType() === 'callback_query') {
                $callBackQueryService->index($botInfo, $updateData, $telegram);
            }
            if (
                $updateData->objectType() === 'message' &&
                ! $updateData->getMessage()->hasCommand() &&
                ! $updateData->getChat()->has('group') &&
                ! $updateData->getChat()->has('supergroup') &&
                ! $updateData->getChat()->has('getChat') &&
                ! in_array($updateData->getChat()->type, ['group', 'supergroup'])
            ) {
                if ($updateData->getChat()->type != 'private') {
                    return 'ok';
                }
                $startService->index($botInfo,$updateData,$telegram);
            }
        }

        dd($response);
    }
}
