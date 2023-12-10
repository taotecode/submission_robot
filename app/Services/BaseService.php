<?php

namespace App\Services;

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
                //            'certificate' => '',
                //            'max_connections' => '',
                //            'allowed_updates' => '',
            ];
            \Illuminate\Support\Facades\Log::info('设置Web Hook', $params);

            return $telegram->setWebhook($params);
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
            $commands=[
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
                'type' => 'all_private_chats',
            ];
            $params = [
                'commands' => $commands,
                'scope' => $scope,
            ];
            $telegram = new Api($token);
            return $telegram->setMyCommands($params);
        } catch (TelegramSDKException $e) {
            throw new Exception($e);
        }
    }
}
