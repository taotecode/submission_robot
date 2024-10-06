<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use lucadevelop\TelegramEntitiesDecoder\EntityDecoder;
use Telegram\Bot\Api;
use Telegram\Bot\Helpers\Entities;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class TestController extends Controller
{
    public function pa()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        /*$reply_markup = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::button([
                    'text' => 'Google',
                    'url' => 'https://www.google.com',
                ]),
            ]);
        dd($telegram->sendMessage([
            'chat_id' => '6247385123',
            'text' => 'ceshi',
            'parse_mode' => 'HTML',
            'reply_markup'=>$reply_markup,
        ]));*/

        //        dd($telegram->deleteWebhook());

        //        $text=new Entities("zhelsa <b>zhelsa</b>");
        //        dump($text->toMarkdown());
        $entity_decoder = new EntityDecoder('HTML');
        $response = $telegram->getUpdates([
            //            'offset'=>1,
        ]);
        dump($response);
        foreach ($response as $item) {
            dump($item->getMessage());
            $decoded_text = $entity_decoder->decode($item->getMessage());
            dump($decoded_text);
        }
        //        dd($response);
    }

    public function setCommands()
    {
        $data=(new BaseService)->setCommands(env('TELEGRAM_BOT_TOKEN'));
        dd($data);
        /*$telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
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
            'commands' => json_encode([
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
            'commands' => json_encode([
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
                ],
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
        ]);*/
    }

    public function webapp()
    {
        /*$telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        //设置web app
        $response = $telegram->getMe();
        dd($response);*/

        $bot = Bot::find(1);
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
        Log::info('', $updates->toArray());

        $telegram->setMyCommands([
            'chat_id' => $updates->getChat()->id,
            'text' => '你的网页名称',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '你的网页名称', 'url' => 'https://www.baidu.com'],
                    ],
                ],
            ]),
        ]);
    }
}
