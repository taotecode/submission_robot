<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\BaseService;
use App\Telegram\Commands\StartCommand;
use Telegram\Bot\Api;

class TestController extends Controller
{
    public function pa()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        dd($telegram->sendMessage([
            'chat_id' => '6247385123',
            'text' => 'test',
            'parse_mode' => 'MarkdownV2',
        ]));
    }


    public function setCommands()
    {
        //        ini_set('memory_limit', '100M');
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        //        $data=(new BaseService)->setCommands(env('TELEGRAM_BOT_TOKEN'));
        //        dump($data);BotCommandScopeAllPrivateChats
        $commands = [
            [
                'command' => 'start',
                'description' => '开始投稿',
            ],
            [
                'command' => 'help',
                'description' => '帮助中心',
            ],
        ];
        $telegram->setMyCommands([
            'commands' => json_encode([]),
            'scope' => [
                'type' => 'default',
            ],
        ]);
        $telegram->setMyCommands([
            'commands'=>json_encode([
                [
                    'command' => 'get_group_id',
                    'description' => '获取群组ID',
                ],
            ]),
            'scope' => json_encode([
                'type' => 'all_group_chats',
            ]),
        ]);
        $telegram->setMyCommands([
            'commands'=>json_encode([
                [
                    'command' => 'start',
                    'description' => '开始投稿',
                ],
                [
                    'command' => 'help',
                    'description' => '帮助中心',
                ],
                [
                    'command' => 'get_me_id',
                    'description' => '获取我的ID',
                ]
            ]),
            'scope' => json_encode([
                'type' => 'all_private_chats',
            ]),
        ]);
        $telegram->setMyCommands([
            'commands' => json_encode($commands),
            'scope' => json_encode([
                'type' => 'default',
            ]),
        ]);
    }
}
