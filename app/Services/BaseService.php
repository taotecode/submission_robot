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
}
