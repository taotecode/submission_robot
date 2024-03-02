<?php

namespace App\Services;

use App\Enums\KeyBoardData;
use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendPostedByMessageService
{
    public function sendPostedByMessage(Api $telegram, Manuscript $manuscript, Bot $botInfo, $status)
    {
        switch ($status) {
            case ManuscriptStatus::APPROVED:
                try {

                    $text = get_config('submission.review_approved_submission');

                    $text .= "\r\n\r\n稿件消息直达链接：<a href='https://t.me/" . $botInfo->channel->name . "/" . $manuscript->message_id . "'>" . $manuscript->text . "</a>";

                    $telegram->sendMessage([
                        'chat_id' => $manuscript->posted_by['id'],
                        'text' => $text,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START),
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case ManuscriptStatus::REJECTED:
                try {
                    $telegram->sendMessage([
                        'chat_id' => $manuscript->posted_by['id'],
                        'text' => get_config('submission.review_rejected_submission'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START),
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case ManuscriptStatus::DELETE:
                try {
                    $telegram->sendMessage([
                        'chat_id' => $manuscript->posted_by['id'],
                        'text' => get_config('submission.review_delete_submission'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START),
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
        }
    }
}
