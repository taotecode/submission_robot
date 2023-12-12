<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendPreviewMessageService
{
    public function sendPreviewMessage(Api $telegram, $botInfo, $chatId, $message, $objectType): ?string
    {
        switch ($objectType) {
            case 'text':
                $text = $message['text'];
                //加入自定义尾部内容
                if (! empty($botInfo->tail_content)) {
                    $text .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                }
                try {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $text,
                        'parse_mode' => 'HTML',
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error(addslashes($text));
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'photo':
                $file_id = $message['photo'][0]['file_id'];
                $caption = $message['caption'] ?? '';
                if (! empty($botInfo->tail_content)) {
                    $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                }
                try {
                    $telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => $file_id,
                        'caption' => $caption,
                        'parse_mode' => 'HTML',
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'media_group_photo':
                $media = [];
                foreach ($message as $key => $item) {
                    if ($key == 0) {
                        $caption = $item['caption'] ?? '';
                        if (! empty($botInfo->tail_content)) {
                            $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                        }
                        $media[] = [
                            'type' => 'photo',
                            'media' => $item['photo'][0]['file_id'],
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                        ];
                    } else {
                        $media[] = [
                            'type' => 'photo',
                            'media' => $item['photo'][0]['file_id'],
                        ];
                    }
                }
                try {
                    $telegram->sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ]);

                    return 'ok';
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
                $caption = $message['caption']??'';
                if (! empty($botInfo->tail_content)) {
                    $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                }
                try {
                    $telegram->sendVideo([
                        'chat_id' => $chatId,
                        'video' => $file_id,
                        'duration' => $duration,
                        'width' => $width,
                        'height' => $height,
                        'caption' => $caption,
                        'parse_mode' => 'HTML',
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'media_group_video':
                $media = [];
                foreach ($message as $key => $item) {
                    if ($key == 0) {
                        $caption = $item['caption'] ?? '';
                        if (! empty($botInfo->tail_content)) {
                            $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                        }
                        $media[] = [
                            'type' => 'video',
                            'media' => $item['video']['file_id'],
                            'duration' => $item['video']['duration'],
                            'width' => $item['video']['width'],
                            'height' => $item['video']['height'],
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
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
                    $telegram->sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ]);

                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'audio':
                $file_id = $message['audio']['file_id'];
                $duration = $message['audio']['duration'];
                $title = $message['audio']['file_name'];
                $caption = $message['caption']??"";
                if (! empty($botInfo->tail_content)) {
                    $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                }
                try {
                    $telegram->sendAudio([
                        'chat_id' => $chatId,
                        'audio' => $file_id,
                        'duration' => $duration,
                        'caption' => $caption,
                        'title' => $title,
                        'parse_mode' => 'HTML',
                    ]);

                    return 'ok';
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
                    if (! empty($botInfo->tail_content)) {
                        $text .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                    }
                    try {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $text,
                            'parse_mode' => 'HTML',
                        ]);
                        $telegram->sendMediaGroup([
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ]);

                        return 'ok';
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
                        $telegram->sendMediaGroup([
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ]);

                        return 'ok';
                    } catch (TelegramSDKException $telegramSDKException) {
                        Log::error($telegramSDKException);

                        return 'error';
                    }
                }
                break;
            default:
                return 'error';
        }
    }

    public function sendEmptyMessage(Api $telegram, $chatId)
    {

    }
}
