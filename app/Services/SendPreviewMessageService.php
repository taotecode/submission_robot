<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendPreviewMessageService
{
    public function sendPreviewMessage(Api $telegram, $botInfo, $chatId, $message, $objectType): ?string
    {
        if ($botInfo->is_auto_keyword == 1) {
            //检查是否有词库
            $lexiconPath = null;
            if (Storage::exists("public/lexicon_{$botInfo->id}.txt")) {
                $lexiconPath = storage_path("app/public/lexicon_{$botInfo->id}.txt");
            }
        }

        switch ($objectType) {
            case 'text':
                $text = $message['text'] ?? '';
                //自动关键词
                $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $text);
                //加入自定义尾部内容
                $text .= $this->addTailContent($botInfo->tail_content);

                return $this->sendTelegramMessage($telegram, 'sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);
            case 'photo':
                $file_id = $message['photo'][0]['file_id'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $caption);
                //加入自定义尾部内容
                $caption .= $this->addTailContent($botInfo->tail_content);

                return $this->sendTelegramMessage($telegram, 'sendPhoto', [
                    'chat_id' => $chatId,
                    'photo' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            case 'media_group_photo':
                $media = [];
                foreach ($message as $key => $item) {
                    if ($key == 0) {
                        $caption = $item['caption'] ?? '';
                        //自动关键词
                        $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $caption);
                        //加入自定义尾部内容
                        $caption .= $this->addTailContent($botInfo->tail_content);
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

                return $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                    'chat_id' => $chatId,
                    'media' => json_encode($media),
                ]);
            case 'video':
                $file_id = $message['video']['file_id'];
                $duration = $message['video']['duration'];
                $width = $message['video']['width'];
                $height = $message['video']['height'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $caption);
                //加入自定义尾部内容
                $caption .= $this->addTailContent($botInfo->tail_content);

                return $this->sendTelegramMessage($telegram, 'sendVideo', [
                    'chat_id' => $chatId,
                    'video' => $file_id,
                    'duration' => $duration,
                    'width' => $width,
                    'height' => $height,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            case 'media_group_video':
                $media = [];
                foreach ($message as $key => $item) {
                    if ($key == 0) {
                        $caption = $item['caption'] ?? '';
                        //自动关键词
                        $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $caption);
                        //加入自定义尾部内容
                        $caption .= $this->addTailContent($botInfo->tail_content);
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

                return $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                    'chat_id' => $chatId,
                    'media' => json_encode($media),
                ]);
            case 'audio':
                $file_id = $message['audio']['file_id'];
                $duration = $message['audio']['duration'];
                $title = $message['audio']['file_name'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $caption);
                //加入自定义尾部内容
                $caption .= $this->addTailContent($botInfo->tail_content);

                return $this->sendTelegramMessage($telegram, 'sendAudio', [
                    'chat_id' => $chatId,
                    'audio' => $file_id,
                    'duration' => $duration,
                    'caption' => $caption,
                    'title' => $title,
                    'parse_mode' => 'HTML',
                ]);
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
                    //自动关键词
                    $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $text);
                    //加入自定义尾部内容
                    $text .= $this->addTailContent($botInfo->tail_content);
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

                    return $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ]);
                }
                break;
            default:
                return 'error';
        }
    }

    private function addKeyWord($is_auto_keyword, $keyword, $lexiconPath, $text): string
    {
        if (empty($keyword)) {
            return '';
        }
        if (empty($text)) {
            return '';
        }
        if ($is_auto_keyword == 1) {
            //分词
            $quickCut = quickCut($text, $lexiconPath);
            $keywords = [];
            foreach ($quickCut as $item) {
                if (in_array($item, $keyword)) {
                    $keywords[] = $item;
                }
            }
            if (! empty($keywords)) {
                $textContent = PHP_EOL.PHP_EOL.'关键词：';
                foreach ($keywords as $item) {
                    $textContent .= "#{$item} ";
                }

                return $textContent;
            }
        }

        return '';
    }

    private function addTailContent($tail_content): string
    {
        if (! empty($tail_content)) {
            return PHP_EOL.PHP_EOL.$tail_content;
        }

        return '';
    }

    private function sendTelegramMessage($telegram, $method, $params): string
    {
        try {
            $telegram->$method($params);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
