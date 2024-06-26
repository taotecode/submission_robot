<?php

namespace App\Services;

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

                    if (empty(get_text_title($manuscript->text))) {
                        //                        $text .= "\r\n\r\n稿件消息直达链接：<a href='https://t.me/".$manuscript->channel->name.'/'.$manuscript->message_id."'>点击查看</a>";
                        $text = str($text)->swap([
                            '{url}' => 'https://t.me/'.$manuscript->channel->name.'/'.$manuscript->message_id,
                            '{title}' => '点击查看',
                        ]);
                    } else {
                        //                        $text .= "\r\n\r\n稿件消息直达链接：<a href='https://t.me/".$manuscript->channel->name.'/'.$manuscript->message_id."'>".get_text_title($manuscript->text).'</a>';
                        $text = str($text)->swap([
                            '{url}' => 'https://t.me/'.$manuscript->channel->name.'/'.$manuscript->message_id,
                            '{title}' => get_text_title($manuscript->text),
                        ]);
                    }

                    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

                    $telegram->sendMessage([
                        'chat_id' => $manuscript->posted_by['id'],
                        'text' => $text,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
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
                        'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
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
                        'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
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
