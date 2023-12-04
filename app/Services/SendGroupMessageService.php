<?php

namespace App\Services;

use App\Enums\KeyBoardData;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendGroupMessageService
{
    /**
     * 发送到审核群组
     */
    public function sendGroupMessage(Api $telegram, $botInfo, $message, $objectType, $manuscriptId): string
    {
        if (! empty($botInfo->review_group->name)) {
            $chatId = '@'.$botInfo->review_group->name;
        } else {
            $chatId = $botInfo->review_group->group_id;
        }

        $review_num = $botInfo->review_num;

        $inline_keyboard = KeyBoardData::REVIEW_GROUP;

        $inline_keyboard['inline_keyboard'][0][0]['text'] .= "(0/$review_num)";
        $inline_keyboard['inline_keyboard'][0][0]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][1]['text'] .= "(0/$review_num)";
        $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][2]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][1][0]['callback_data'] .= ":$manuscriptId";
        $inline_keyboard['inline_keyboard'][1][1]['callback_data'] .= ":$manuscriptId";

        switch ($objectType) {
            case 'text':
                $text = $message['text'];
                //加入自定义尾部内容
                $text .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                try {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $text,
                        'reply_markup' => json_encode($inline_keyboard),
                        'parse_mode' => 'MarkdownV2',
                    ]);

                    return $text;
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'photo':
                $file_id = $message['photo'][0]['file_id'];
                $caption = $message['caption'] ?? '';
                $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                try {
                    $telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => $file_id,
                        'caption' => $caption,
                        'reply_markup' => json_encode($inline_keyboard),
                        'parse_mode' => 'MarkdownV2',
                    ]);

                    return $caption;
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'media_group_photo':
                $media = [];
                $caption = '';
                foreach ($message as $key => $item) {
                    if ($key == 0) {
                        $caption = $item['caption'] ?? '';
                        $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                        $media[] = [
                            'type' => 'photo',
                            'media' => $item['photo'][0]['file_id'],
                            'caption' => $caption,
                            'parse_mode' => 'MarkdownV2',
                        ];
                    } else {
                        $media[] = [
                            'type' => 'photo',
                            'media' => $item['photo'][0]['file_id'],
                        ];
                    }
                }
                try {
                    $telegramMessage=$telegram->sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ]);

                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "收到包含多张图片的提交 👆",
                        'reply_to_message_id' => $telegramMessage[0]['message_id'],
                        'reply_markup' => json_encode($inline_keyboard),
                        'parse_mode' => 'MarkdownV2',
                    ]);

                    return $caption;
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'video':
                $file_id = $message['video']['file_id'];
                $duration = $message['video']['duration'];
                $width = $message['video']['width'];
                $height = $message['video']['height'];
                $caption = $message['caption'];
                $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                try {
                    $telegram->sendVideo([
                        'chat_id' => $chatId,
                        'video' => $file_id,
                        'duration' => $duration,
                        'width' => $width,
                        'height' => $height,
                        'caption' => $caption,
                        'reply_markup' => json_encode($inline_keyboard),
                        'parse_mode' => 'MarkdownV2',
                    ]);

                    return $caption;
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'media_group_video':
                $media = [];
                $caption = '';
                foreach ($message as $key => $item) {
                    if ($key == 0) {
                        $caption = $item['caption'] ?? '';
                        $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                        $media[] = [
                            'type' => 'video',
                            'media' => $item['video']['file_id'],
                            'duration' => $item['video']['duration'],
                            'width' => $item['video']['width'],
                            'height' => $item['video']['height'],
                            'caption' => $caption,
                            'parse_mode' => 'MarkdownV2',
                        ];
                    } else {
                        $media[] = [
                            'type' => 'video',
                            'media' => $item['video']['file_id'],
                            'duration' => $item['video']['duration'],
                            'width' => $item['video']['width'],
                            'height' => $item['video']['height'],
                        ];
                    }
                }
                try {
                    $telegramMessage=$telegram->sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ]);

                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "收到包含多个视频的提交 👆",
                        'reply_to_message_id' => $telegramMessage[0]['message_id'],
                        'reply_markup' => json_encode($inline_keyboard),
                        'parse_mode' => 'MarkdownV2',
                    ]);

                    return $caption;
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'audio':
                $file_id = $message['audio']['file_id'];
                $duration = $message['audio']['duration'];
                $title = $message['audio']['file_name'];
                $caption = $message['caption'];
                $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                try {
                    $telegram->sendAudio([
                        'chat_id' => $chatId,
                        'audio' => $file_id,
                        'duration' => $duration,
                        'caption' => $caption,
                        'title' => $title,
                        'reply_markup' => json_encode($inline_keyboard),
                        'parse_mode' => 'MarkdownV2',
                    ]);

                    return $caption;
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'media_group_audio':
                if (isset($message['text'])) {
                    $textMessage = $message['text'];
                    $audioMessage = $message['audio'];
                    $media = [];
                    foreach ($audioMessage as $key => $item) {
                        $media[] = [
                            'type' => 'audio',
                            'media' => $item['audio']['file_id'],
                            'title' => $item['audio']['file_name'],
                            'duration' => $item['audio']['duration'],
                        ];
                    }
                    $text = $textMessage['text'];
                    //加入自定义尾部内容
                    $text .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                    try {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $text,
                            'parse_mode' => 'MarkdownV2',
                        ]);

                        $telegramMessage=$telegram->sendMediaGroup([
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ]);

                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "收到包含多个音频的提交 👆",
                            'reply_to_message_id' => $telegramMessage[0]['message_id'],
                            'reply_markup' => json_encode($inline_keyboard),
                            'parse_mode' => 'MarkdownV2',
                        ]);

                        return $text;
                    } catch (TelegramSDKException $telegramSDKException) {
                        Log::error($telegramSDKException);

                        return 'error';
                    }
                } else {
                    $media = [];
                    foreach ($message as $key => $item) {
                        $media[] = [
                            'type' => 'audio',
                            'media' => $item['audio']['file_id'],
                            'title' => $item['audio']['file_name'],
                            'duration' => $item['audio']['duration'],
                        ];
                    }
                    try {
                        $telegramMessage=$telegram->sendMediaGroup([
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ]);

                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "收到包含多个音频的提交 👆",
                            'reply_to_message_id' => $telegramMessage[0]['message_id'],
                            'reply_markup' => json_encode($inline_keyboard),
                            'parse_mode' => 'MarkdownV2',
                        ]);

                        return '';
                    } catch (TelegramSDKException $telegramSDKException) {
                        Log::error($telegramSDKException);

                        return 'error';
                    }
                }
                break;
        }
    }
}
