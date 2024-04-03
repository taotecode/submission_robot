<?php

namespace App\Services\CallBackQuery;

use App\Models\Manuscript;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ManuscriptSearchService
{

    public function link(Api $telegram, $botInfo,?Manuscript $manuscript,$chatId)
    {
        $url="https://t.me/".$botInfo->channel->name."/".$manuscript->message_id;

        $text= "<a href='$url'>【{$manuscript->text}】</a>";

        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]);
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);
            return 'error';
        }
    }

}
