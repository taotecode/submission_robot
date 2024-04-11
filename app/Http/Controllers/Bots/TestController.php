<?php

namespace App\Http\Controllers\Bots;

use App\Enums\ManuscriptStatus;
use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\BaseService;
use App\Telegram\Commands\StartCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class TestController extends Controller
{
    public function pa()
    {
//        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

//        $file=$telegram->getFile(['file_id'=>'AgACAgEAAxkBAAIB2GYPabtpIuMVtmNIYreBYbEagoq7AALHqzEbvsSARJrVHae73dkSAQADAgADcwADNAQ']);
//        dd($file);

//        $text="稿件消息<a href='https://t.me/123'>123</a>";
//
//        dd($telegram->sendMessage([
//            'chat_id' => '6247385123',
//            'text' => $text,
//            'parse_mode' => 'HTML',
//        ]));
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

    public function webapp()
    {
        /*$telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        //设置web app
        $response = $telegram->getMe();
        dd($response);*/

        $bot=Bot::find(1);
//        Cache::put('test', [
//            'bot'=>$bot,
//            'asd'=>[
//                123
//            ]
//        ], 60*60*24*7);


        dd(Cache::get('test'));
    }

    public function webapp_hook()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $updates = $telegram->commandsHandler(true);
        Log::info('',$updates->toArray());

        $telegram->setMyCommands([
            'chat_id' => $updates->getChat()->id,
            'text' => '你的网页名称',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '你的网页名称', 'url' => 'https://www.baidu.com'],
                    ]
                ]
            ])
        ]);
    }
}
