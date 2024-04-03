<?php

namespace App\Services\CallBackQuery;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class PendingManuscriptService
{
    public function refresh(Api $telegram, $botInfo,$chatId,$messageId)
    {
        $inline_keyboard=[
            'inline_keyboard' => [
                [
                    [
                        'text' => '刷新 🔄',
                        'callback_data' => 'refresh_pending_manuscript_list',
                    ],
                ],
            ],
        ];

        $manuscript = (new \App\Models\Manuscript())->where('bot_id', $botInfo->id)->where('status', 0)->get();
        if (!$manuscript->isEmpty()){
            foreach ($manuscript as $item){
                $inline_keyboard['inline_keyboard'][] = [
                    [
                        'text' => "【".$item->text."】",
                        'callback_data' => 'show_pending_manuscript:'.$item->id,
                    ],
                ];
            }
        }
        try {
            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode($inline_keyboard),
            ]);
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);
            return 'error';
        }
    }
}
