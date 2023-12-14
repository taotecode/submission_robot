<?php

namespace App\Services;

use App\Models\Bot;
use Exception;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BaseService
{
    /**
     * @throws Exception
     */
    public function setWebHook($botId, $token): bool
    {
        $url = config('app.url').'/api/bots/hook/'.$botId;

        if (config('app.env') == 'local') {
            $url = 'https://bot.modg.asia/api/bots/hook/'.$botId;
        }

        try {
            $telegram = new Api($token);
            $params = [
                'url' => $url,
            ];
            if ($telegram->setWebhook($params)){
                $bot=Bot::find($botId);
                $bot->webhook_status=1;
                $bot->save();
                return true;
            }
            return false;
        } catch (TelegramSDKException $e) {
            throw new Exception($e);
        }
    }

    public function delWebHook($botId, $token): bool
    {
        try {
            $telegram = new Api($token);
            if ($telegram->deleteWebhook()){
                $bot=Bot::find($botId);
                $bot->webhook_status=0;
                $bot->save();
                return true;
            }
            return false;
        } catch (TelegramSDKException $e) {
            throw new Exception($e);
        }
    }

    /**
     * @throws Exception
     */
    public function setCommands($token): bool
    {
        try {
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
            $scope = [
                'type' => 'default',
            ];
            $params = [
                'commands' => $commands,
                'scope' => $scope,
            ];
            $telegram = new Api($token);
            $telegram->setMyCommands([
                'commands' => json_encode([]),
                'scope' => [
                    'type' => 'default',
                ],
            ]);
            $telegram->setMyCommands([
                'commands'=>json_encode([]),
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
                ]),
                'scope' => json_encode([
                    'type' => 'all_private_chats',
                ]),
            ]);
            return $telegram->setMyCommands([
                'commands' => json_encode($commands),
                'scope' => json_encode([
                    'type' => 'default',
                ]),
            ]);
        } catch (TelegramSDKException $e) {
            throw new Exception($e);
        }
    }
}
