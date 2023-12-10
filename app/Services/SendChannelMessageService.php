<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendChannelMessageService
{
    public function sendChannelMessage(Api $telegram, $botInfo, Manuscript $manuscript)
    {

        $channelList = $botInfo->channel_ids;
        $channelListData = Channel::whereIn('id', $channelList)->get();
        $channelListData = $channelListData->pluck('name')->toArray();

        $message = $manuscript->data;

        switch ($manuscript->type) {
            case 'text':
                $text = $message['text'];

                //是否匿名
                if ($manuscript->is_anonymous === 1) {
                    $text .= PHP_EOL.PHP_EOL.'匿名投稿';
                } else {
                    $text .= PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
                }

                //加入自定义尾部内容
                $text .= PHP_EOL.PHP_EOL.$botInfo->tail_content.'_';

                try {
                    $channelMessageId = [];
                    foreach ($channelListData as $channelUsername) {
                        $channelMessageId[] = $telegram->sendMessage([
                            'chat_id' => '@'.$channelUsername,
                            'text' => $text,
                            'parse_mode' => 'HTML',
                        ])->messageId;
                    }

                    return $channelMessageId;
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($text);
                    Log::error($telegramSDKException);

                    return 'error';
                }
                break;
            case 'photo':
                $file_id = $message['photo'][0]['file_id'];
                $caption = $message['caption'] ?? '';

                //是否匿名
                if ($manuscript->is_anonymous === 1) {
                    $caption .= PHP_EOL.PHP_EOL.'匿名投稿';
                } else {
                    $caption .= PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
                }

                $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                try {
                    foreach ($channelListData as $channelUsername) {
                        $telegram->sendPhoto([
                            'chat_id' => '@'.$channelUsername,
                            'photo' => $file_id,
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                        ]);
                    }

                    return 'ok';
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

                        //是否匿名
                        if ($manuscript->is_anonymous === 1) {
                            $caption .= PHP_EOL.PHP_EOL.'匿名投稿';
                        } else {
                            $caption .= PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
                        }

                        $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
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
                    foreach ($channelListData as $channelUsername) {
                        $telegram->sendMediaGroup([
                            'chat_id' => '@'.$channelUsername,
                            'media' => json_encode($media),
                        ]);
                    }

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
                $caption = $message['caption'];

                //是否匿名
                if ($manuscript->is_anonymous === 1) {
                    $caption .= PHP_EOL.PHP_EOL.'匿名投稿';
                } else {
                    $caption .= PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
                }

                $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                try {
                    foreach ($channelListData as $channelUsername) {
                        $telegram->sendVideo([
                            'chat_id' => '@'.$channelUsername,
                            'video' => $file_id,
                            'duration' => $duration,
                            'width' => $width,
                            'height' => $height,
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                        ]);
                    }

                    return 'ok';
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

                        //是否匿名
                        if ($manuscript->is_anonymous === 1) {
                            $caption .= PHP_EOL.PHP_EOL.'匿名投稿';
                        } else {
                            $caption .= PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
                        }

                        $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
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
                    foreach ($channelListData as $channelUsername) {
                        $telegram->sendMediaGroup([
                            'chat_id' => '@'.$channelUsername,
                            'media' => json_encode($media),
                        ]);
                    }

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
                $caption = $message['caption'];

                //是否匿名
                if ($manuscript->is_anonymous === 1) {
                    $caption .= PHP_EOL.PHP_EOL.'匿名投稿';
                } else {
                    $caption .= PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
                }

                $caption .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                try {
                    foreach ($channelListData as $channelUsername) {
                        $telegram->sendAudio([
                            'chat_id' => '@'.$channelUsername,
                            'audio' => $file_id,
                            'duration' => $duration,
                            'caption' => $caption,
                            'title' => $title,
                            'parse_mode' => 'HTML',
                        ]);
                    }

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

                    //是否匿名
                    if ($manuscript->is_anonymous === 1) {
                        $text .= PHP_EOL.PHP_EOL.'匿名投稿';
                    } else {
                        $text .= PHP_EOL.PHP_EOL.'投稿人：'.get_posted_by($manuscript->posted_by);
                    }

                    //加入自定义尾部内容
                    $text .= PHP_EOL.PHP_EOL.$botInfo->tail_content;
                    try {
                        foreach ($channelListData as $channelUsername) {
                            $telegram->sendMessage([
                                'chat_id' => '@'.$channelUsername,
                                'text' => $text,
                                'parse_mode' => 'HTML',
                            ]);
                            $telegram->sendMediaGroup([
                                'chat_id' => '@'.$channelUsername,
                                'media' => json_encode($media),
                            ]);
                        }

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
                        foreach ($channelListData as $channelUsername) {
                            $telegram->sendMediaGroup([
                                'chat_id' => '@'.$channelUsername,
                                'media' => json_encode($media),
                            ]);
                        }

                        return 'ok';
                    } catch (TelegramSDKException $telegramSDKException) {
                        Log::error($telegramSDKException);

                        return 'error';
                    }
                }
                break;
        }
    }
}
