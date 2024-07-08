<?php

namespace App\Services\CallBackQuery\Submission;

use App\Enums\CacheKey;
use App\Enums\InlineKeyBoardData;
use App\Enums\KeyBoardData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ForwardOriginService
{

    public function forward_origin_select_Yes(Api $telegram, $chatId,$messageId)
    {
        $cacheTag=CacheKey::Submission.'.'.$chatId;
        $objectType=Cache::tags($cacheTag)->get('objectType');
        $messageCache = Cache::tags($cacheTag)->get($objectType);
        $messageCache['forward_origin_type'] = 1;
        Cache::tags($cacheTag)->put($objectType, $messageCache);
        Cache::tags($cacheTag)->put('forward_origin_type', 1, now()->addDay());

        try {
            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => get_config('submission.select_forward_origin_yes_tip'),
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text'=>get_keyboard_name_config('submission.forward_origin_select_restart'),
                                'callback_data'=>'forward_origin_select_restart'
                            ],
                        ]
                    ]
                ]),
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    public function forward_origin_select_No(Api $telegram, $chatId,$messageId)
    {
        $cacheTag=CacheKey::Submission.'.'.$chatId;
        $objectType=Cache::tags($cacheTag)->get('objectType');
        $messageCache = Cache::tags($cacheTag)->get($objectType);
        $messageCache['forward_origin_type'] = 2;
        Cache::tags($cacheTag)->put($objectType, $messageCache);
        Cache::tags($cacheTag)->put('forward_origin_type', 2, now()->addDay());

        try {
            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => get_config('submission.select_forward_origin_no_tip'),
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text'=>get_keyboard_name_config('submission.forward_origin_select_restart'),
                                'callback_data'=>'forward_origin_select_restart'
                            ],
                        ]
                    ]
                ]),
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    public function forward_origin_select_restart(Api $telegram, $chatId,$messageId)
    {
        $cacheTag=CacheKey::Submission.'.'.$chatId;
        $objectType=Cache::tags($cacheTag)->get('objectType');
        $messageCache = Cache::tags($cacheTag)->get($objectType);
        $messageCache['forward_origin_type'] = 0;
        Cache::tags($cacheTag)->put($objectType, $messageCache);
        Cache::tags($cacheTag)->put('forward_origin_type', 0, now()->addDay());
        try {
            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => get_config('submission.select_forward_origin_is_tip'),
                'reply_markup' => json_encode(InlineKeyBoardData::$FORWARD_ORIGIN_SELECT),
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    public function forward_origin_input_cancel(Api $telegram, mixed $chatId, mixed $messageId)
    {
        $cacheTag=CacheKey::Submission.'.'.$chatId;
        $objectType=Cache::tags($cacheTag)->get('objectType');
        $messageCache = Cache::tags($cacheTag)->get($objectType);
        try {

            $telegram->deleteMessage([
                'chat_id' => $chatId,
                'message_id' => Cache::tags($cacheTag)->get('forward_origin_input_id'),
            ]);

            $telegram->deleteMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => get_config('submission.select_forward_origin_input_cancel_tip'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::$START_SUBMISSION),
            ]);


            $messageCache['forward_origin_input_status'] = 2;
            Cache::tags($cacheTag)->put($objectType, $messageCache);
            Cache::tags($cacheTag)->put('forward_origin_input_status', 2, now()->addDay());
            Cache::tags($cacheTag)->put('forward_origin_input_id',0, now()->addDay());
            Cache::tags($cacheTag)->put('forward_origin_input_data',0, now()->addDay());

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
