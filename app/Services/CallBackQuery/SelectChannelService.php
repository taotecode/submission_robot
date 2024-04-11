<?php

namespace App\Services\CallBackQuery;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Services\SubmissionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class SelectChannelService
{
    public function select(Api $telegram, $botInfo, mixed $chatId, mixed $messageId, $callbackQueryId, array $commandArray): string
    {
        $channelId = $commandArray[2];
        if (empty($channelId)) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => '频道ID不能为空！',
                    'show_alert' => true,
                ]);
                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }
        Cache::tags(CacheKey::Submission.'.'.$chatId)->put('channel_id', $channelId, now()->addDay());

        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.select_channel_end'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::SELECT_CHANNEL_END),
            ]);
            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);
            return 'error';
        }
    }
}
