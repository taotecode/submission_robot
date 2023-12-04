<?php

namespace App\Services;

use App\Models\Manuscript;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendPostedByMessageService
{
    public function sendPostedByMessage(Api $telegram, Manuscript $manuscript, $status)
    {
        switch ($status) {
            case 1:
                try {
                    $telegram->sendMessage([
                        'chat_id' => $manuscript->posted_by['id'],
                        'text' => get_config('submission.review_approved_submission'),
                        'parse_mode' => 'MarkdownV2',
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 2:
                try {
                    $telegram->sendMessage([
                        'chat_id' => $manuscript->posted_by['id'],
                        'text' => get_config('submission.review_rejected_submission'),
                        'parse_mode' => 'MarkdownV2',
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
