<?php

namespace App\Services;

use App\Enums\KeyBoardData;
use App\Models\Channel;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendTelegramMessageService
{
    public function sendPreviewMessage(Api $telegram, $botInfo, string $chatId, array $message, string $objectType): mixed
    {
        return $this->objectTypeHandle($telegram, $botInfo, $chatId, $objectType, $message);
    }

    public function sendGroupMessage(Api $telegram, $botInfo, $message, $objectType, $manuscriptId): mixed
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

        return $this->objectTypeHandle($telegram, $botInfo, $chatId, $objectType, $message, $inline_keyboard, true, true);
    }

    public function sendChannelMessage(Api $telegram, $botInfo, Manuscript $manuscript): mixed
    {
        $channelList = $botInfo->channel_ids;
        $channelListData = Channel::whereIn('id', $channelList)->get();
        $channelListData = $channelListData->pluck('name')->toArray();

        $message = $manuscript->data;

        $objectType = $manuscript->type;

        return $this->objectTypeHandle($telegram, $botInfo, $channelListData, $objectType, $message, null, false, false, $manuscript, true);
    }

    private function objectTypeHandle(Api $telegram, $botInfo, $chatId, $objectType, $message, array $inline_keyboard = null, bool $isReviewGroup = false, bool $isReturnText = false, $manuscript = null, bool $isChannel = false): mixed
    {
        if (empty($inline_keyboard)) {
            $inline_keyboard = null;
        } else {
            $inline_keyboard = json_encode($inline_keyboard);
        }

        $lexiconPath = null;
        if ($botInfo->is_auto_keyword == 1) {
            //检查是否有词库
            if (Storage::exists("public/lexicon_{$botInfo->id}.txt")) {
                $lexiconPath = storage_path("app/public/lexicon_{$botInfo->id}.txt");
            }
        }

        switch ($objectType) {
            case 'text':
                $text = $message['text'] ?? '';
                //自动关键词
                $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $text);
                // 加入匿名
                $text .= $this->addAnonymous($manuscript);
                //加入自定义尾部内容
                $text .= $this->addTailContent($botInfo->tail_content);
                $result = $this->sendTelegramMessage($telegram, 'sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ], false, $isChannel);
                if ($isReturnText) {
                    return $text;
                }
                if (is_string($result) || $isChannel) {
                    return $result;
                } else {
                    return 'ok';
                }
            case 'photo':
                $file_id = $message['photo'][0]['file_id'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $caption);
                //加入自定义尾部内容
                $caption .= $this->addTailContent($botInfo->tail_content);

                $result = $this->sendTelegramMessage($telegram, 'sendPhoto', [
                    'chat_id' => $chatId,
                    'photo' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ], false, $isChannel);
                if ($isReturnText) {
                    return $caption;
                }
                if (is_string($result) || $isChannel) {
                    return $result;
                } else {
                    return 'ok';
                }
            case 'media_group_photo':
                $media = [];
                $caption = '';
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
                $result = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                    'chat_id' => $chatId,
                    'media' => json_encode($media),
                ], true, $isChannel);

                if ($isReviewGroup) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '收到包含多张图片的提交 👆',
                        'reply_to_message_id' => $result[0]['message_id'],
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                }
                if ($isReturnText) {
                    return $caption;
                }
                if (is_string($result) || $isChannel) {
                    return $result;
                } else {
                    return 'ok';
                }
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
                $result = $this->sendTelegramMessage($telegram, 'sendVideo', [
                    'chat_id' => $chatId,
                    'video' => $file_id,
                    'duration' => $duration,
                    'width' => $width,
                    'height' => $height,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ], false, $isChannel);
                if ($isReturnText) {
                    return $caption;
                }
                if (is_string($result) || $isChannel) {
                    return $result;
                } else {
                    return 'ok';
                }
            case 'media_group_video':
                $media = [];
                $caption = '';
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

                $result = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                    'chat_id' => $chatId,
                    'media' => json_encode($media),
                ], true, $isChannel);

                if ($isReviewGroup) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '收到包含多个视频的提交 👆',
                        'reply_to_message_id' => $result[0]['message_id'],
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                }
                if ($isReturnText) {
                    return $caption;
                }
                if (is_string($result) || $isChannel) {
                    return $result;
                } else {
                    return 'ok';
                }
            case 'audio':
                $file_id = $message['audio']['file_id'];
                $duration = $message['audio']['duration'];
                $title = $message['audio']['file_name'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $caption);
                //加入自定义尾部内容
                $caption .= $this->addTailContent($botInfo->tail_content);

                $result = $this->sendTelegramMessage($telegram, 'sendAudio', [
                    'chat_id' => $chatId,
                    'audio' => $file_id,
                    'duration' => $duration,
                    'caption' => $caption,
                    'title' => $title,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ], false, $isChannel);

                if ($isReturnText) {
                    return $caption;
                }
                if (is_string($result) || $isChannel) {
                    return $result;
                } else {
                    return 'ok';
                }
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
                    $text = $textMessage;
                    //自动关键词
                    $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keywords, $lexiconPath, $text);
                    //加入自定义尾部内容
                    $text .= $this->addTailContent($botInfo->tail_content);
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $text,
                        'parse_mode' => 'HTML',
                    ]);

                    $result = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ], true, $isChannel);

                    if ($isReviewGroup) {
                        $this->sendTelegramMessage($telegram, 'sendMessage', [
                            'chat_id' => $chatId,
                            'text' => '收到包含多个音频的提交 👆',
                            'reply_to_message_id' => $result[0]['message_id'],
                            'parse_mode' => 'HTML',
                            'reply_markup' => $inline_keyboard,
                        ]);
                    }

                    if ($isReturnText) {
                        return $text;
                    }
                    if (is_string($result) || $isChannel) {
                        return $result;
                    } else {
                        return 'ok';
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
                    $result = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ], true, $isChannel);

                    if ($isReviewGroup) {
                        $this->sendTelegramMessage($telegram, 'sendMessage', [
                            'chat_id' => $chatId,
                            'text' => '收到包含多个音频的提交 👆',
                            'reply_to_message_id' => $result[0]['message_id'],
                            'parse_mode' => 'HTML',
                            'reply_markup' => $inline_keyboard,
                        ]);
                    }
                    if ($isReturnText) {
                        return '';
                    }
                    if (is_string($result) || $isChannel) {
                        return $result;
                    } else {
                        return 'ok';
                    }
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

    private function addAnonymous($manuscript): string
    {
        if (! empty($manuscript)) {
            if ($manuscript->is_anonymous === 1) {
                $text = PHP_EOL.PHP_EOL.'匿名投稿';
            } else {
                $text = PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
            }

            return $text;
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

    public function sendTelegramMessage($telegram, string $method, array $params, bool $isReturnTelegramMessage = false, bool $isChannel = false): mixed
    {
        foreach ($params as $key => $value) {
            if (empty($value)) {
                unset($params[$key]);
            }
        }
        try {

            if ($isChannel) {
                $channelMessageId = [];
                $channelListData = $params['chat_id'];
                unset($params['chat_id']);
                foreach ($channelListData as $channelUsername) {
                    $params['chat_id'] = '@'.$channelUsername;
                    $channelMessageId[$channelUsername] = $telegram->$method($params)->messageId;
                }

                return $channelMessageId;
            } else {
                if ($isReturnTelegramMessage) {
                    return $telegram->$method($params);
                }
                $telegram->$method($params);
            }

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
