<?php

namespace App\Services\CallBackQuery;

use App\Enums\ManuscriptStatus;
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
            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);
            return 'error';
        }
    }

    public function page(Api $telegram, $botInfo, ?Manuscript $manuscript, mixed $chatId,$messageId,$callbackQueryId, array $commandArray)
    {
        $keyword = $commandArray[2]??'';
        $page = $commandArray[3]??1;
        $type=$commandArray[1]??'';

        $inline_keyboard=[
            'inline_keyboard' => []
        ];

        $manuscript = (new Manuscript())
            ->where('bot_id', $botInfo->id)
            ->where('status', ManuscriptStatus::APPROVED)
            ->where('text', 'like', '%'.$keyword.'%')
            ->where('data', 'like', '%'.$keyword.'%')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'page', $page);

        if ($manuscript->isEmpty()){
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => '没有找到相关稿件！',
                    'show_alert' => true,
                ]);
                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }

        $manuscript->each(function ($item) use (&$inline_keyboard){
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => $item->text, 'callback_data' => 'manuscript_search_show_link:'.$item->id]
            ];
        });

        if ($manuscript->currentPage() > 1) {
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => '上一页', 'callback_data' => 'manuscript_search_page:prev:'.$keyword.':'.($manuscript->currentPage()-1)],
            ];
        }
        if ($manuscript->lastPage() > $manuscript->currentPage()) {
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => '下一页', 'callback_data' => 'manuscript_search_page:next:'.$keyword.':'.($manuscript->currentPage()+1)],
            ];
        }

        try {
            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode($inline_keyboard),
            ]);
            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);
            return 'error';
        }
    }

}
