<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Telegram\Bot\Api;

class TestController extends Controller
{
    public function setCommands()
    {
        //        ini_set('memory_limit', '100M');
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        //        $data=(new BaseService)->setCommands(env('TELEGRAM_BOT_TOKEN'));
        //        dump($data);BotCommandScopeAllPrivateChats
        $commands = [
            [
                'command' => 'start',
                'description' => '开始使用',
            ],
            [
                'command' => 'help',
                'description' => '帮助',
            ],
        ];
        $scope = [
            'type' => 'BotCommandScopeDefault',
        ];
        $params = [
            'commands' => $commands,
            //            'scope' => $scope,
        ];
        dump($telegram->setMyCommands($params));
        $method = 'getMyCommands';
        dump($telegram->$method());

    }
}
