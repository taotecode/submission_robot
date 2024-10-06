<?php

namespace App\Services;

use App\Enums\InlineKeyBoardData;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendTelegramMessageService
{
    public function sendPreviewMessage(
        Api     $telegram, $botInfo, string $chatId, array $message, string $objectType,
        bool    $is_addKeyWord = true, bool $is_addAnonymous = true, bool $is_addTailContent = true,
        ?string $custom_header_content = null, ?string $custom_tail_content = null
    ): mixed
    {
        return $this->objectTypeHandle(
            $telegram, $botInfo, $chatId, $objectType, $message, null, false, false,
            true, false, null, $is_addKeyWord, $is_addAnonymous, $is_addTailContent,
            $custom_header_content, $custom_tail_content
        );
    }

    /**
     * 发送审核群消息
     *
     * @param null $inline_keyboard
     * @param null $inline_keyboard_enums
     */
    public function sendGroupMessage(
        Api     $telegram, $botInfo, $message, $objectType, $manuscriptId,
                $inline_keyboard = null, $inline_keyboard_enums = null,
        bool    $is_addKeyWord = true, bool $is_addAnonymous = true, bool $is_addTailContent = true,
        ?string $custom_header_content = null, ?string $custom_tail_content = null
    ): mixed
    {
        if (!empty($botInfo->review_group->name)) {
            $chatId = '@' . $botInfo->review_group->name;
        } else {
            $chatId = $botInfo->review_group->group_id;
        }

        $review_approved_num = $botInfo->review_approved_num;
        $review_reject_num = $botInfo->review_reject_num;

        if ($inline_keyboard === null) {
            if (empty($inline_keyboard_enums)) {
                $inline_keyboard = InlineKeyBoardData::REVIEW_GROUP;
            } else {
                $inline_keyboard = $inline_keyboard_enums;
            }

            $inline_keyboard['inline_keyboard'][0][0]['text'] .= "(0/$review_approved_num)";
            $inline_keyboard['inline_keyboard'][0][0]['callback_data'] .= ":$manuscriptId";

            $inline_keyboard['inline_keyboard'][0][1]['text'] .= "(0/$review_reject_num)";
            $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscriptId";

            $inline_keyboard['inline_keyboard'][0][2]['callback_data'] .= ":$manuscriptId";

            $inline_keyboard['inline_keyboard'][1][0]['callback_data'] .= ":$manuscriptId";
            $inline_keyboard['inline_keyboard'][1][1]['callback_data'] .= ":$manuscriptId";
        }

        return $this->objectTypeHandle(
            $telegram, $botInfo, $chatId, $objectType, $message, $inline_keyboard, true, false,
            true, false, null, $is_addKeyWord, $is_addAnonymous, $is_addTailContent,
            $custom_header_content, $custom_tail_content
        );
    }

    /**
     * 发送审核群消息
     */
    public function sendGroupMessageWhiteUser(Api $telegram, $botInfo, $manuscript, $channel): mixed
    {
        if (!empty($botInfo->review_group->name)) {
            $chatId = '@' . $botInfo->review_group->name;
        } else {
            $chatId = $botInfo->review_group->group_id;
        }

        $inline_keyboard = InlineKeyBoardData::$WHITE_LIST_USER_SUBMISSION;
        $inline_keyboard['inline_keyboard'][0][0]['url'] .= $manuscript->channel->name . '/' . $manuscript->message_id;
        $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscript->id";

        $username = get_posted_by($manuscript->posted_by);

        $text = "白名单用户<b>【 {$username} 】</b>的投稿";
        if (empty($manuscript->text)) {
            $text .= '已自动通过审核。';
        } else {
            $text .= "<a href='https://t.me/" . $channel->name . '/' . $manuscript->message_id . "'>“ " . get_text_title($manuscript->text) . ' ”</a> 已自动通过审核。';
        }

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inline_keyboard),
        ]);
    }

    /**
     * 发送频道消息
     */
    public function sendChannelMessage(Api $telegram, $botInfo, Manuscript $manuscript): mixed
    {

        $message = $manuscript->data;

        $objectType = $manuscript->type;

        //频道ID
        if (!empty($manuscript->channel->name)) {
            $chatId = '@' . $manuscript->channel->name;
        } else {
            $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $manuscript->posted_by,
                'text' => '频道ID不存在，请联系管理员',
            ]);

            return false;
        }

        return $this->objectTypeHandle(
            $telegram,
            $botInfo,
            $chatId,
            $objectType,
            $message,
            null,
            false,
            true,
            false,
            true,
            $manuscript,
        );
    }

    /**
     * 根据类型处理
     *
     * @param Api $telegram telegram 实例
     * @param mixed $botInfo 机器人信息
     * @param array|int|string $chatId 频道id或者频道ID数组或者用户id
     * @param string $objectType 类型
     * @param array|null $inline_keyboard 按键
     * @param bool $isReviewGroup 是否是审核群
     * @param bool $isReturnText 是否返回文本
     * @param null $manuscript 投稿信息
     * @return mixed|string
     */
    private function objectTypeHandle(
        Api     $telegram, mixed $botInfo, array|int|string $chatId, string $objectType, $message, ?array $inline_keyboard = null,
        bool    $isReviewGroup = false, bool $isChannel = false, bool $isReturnText = false, bool $isReturnTelegramMessage = false,
                $manuscript = null, bool $is_addKeyWord = true, bool $is_addAnonymous = true, bool $is_addTailContent = true,
        ?string $custom_header_content = null, ?string $custom_tail_content = null
    ): mixed
    {
        if (empty($inline_keyboard)) {
            $inline_keyboard = null;
        } else {
            $inline_keyboard = json_encode($inline_keyboard);
        }

        if ($is_addTailContent) {
            //自定义尾部按钮
            $tail_content_button = $botInfo->tail_content_button;
            if (!empty($tail_content_button) && !$isReviewGroup) {
                $inline_keyboard = json_encode([
                    'inline_keyboard' => $tail_content_button,
                ]);
            }
        }

        $text = '';
        $textStr = '';
        $isReviewGroupText = '';
        $media = [];

        if (!empty($custom_header_content)) {
            $text .= $custom_header_content;
        }

        $method = 'sendMessage';

        $params = [
            'chat_id' => $chatId,
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_keyboard,
        ];

        //公用，仅限单条消息或媒体消息
        if (!empty($message['text']) || !empty($message['caption'])) {
            if (!empty($message['text'])) {
                $text .= $message['text'];
                $textStr = $message['text'];
            }
            if (!empty($message['caption'])) {
                $text .= $message['caption'];
                $textStr = $message['caption'];
            }
            //来源
            if ($botInfo->is_forward_origin == 1) {
                if (isset($message['forward_origin_type']) && $message['forward_origin_type'] == 1) {
                    if (isset($message['forward_origin']['username'])) {
                        $str = str(get_config('submission.forward_origin_text_link'))->swap([
                            '{link}' => "https://t.me/" . $message['forward_origin']['username'] . "/" . $message['forward_origin']['message_id'],
                            '{name}' => $message['forward_from_chat']['title'],
                        ]);
                        $text .= $str;
                    } else {
                        $str = str(get_config('submission.forward_origin_text'))->swap([
                            '{name}' => $message['forward_from_chat']['title'],
                        ]);
                        $text .= $str;
                    }
                }
                if (isset($message['forward_origin_input_status']) && $message['forward_origin_input_status'] == 1) {
                    $str = str(get_config('submission.forward_origin_text'))->swap([
                        '{name}' => $message['forward_origin_input_data'],
                    ]);
                    $text .= $str;
                }
            }
            //自动关键词
            if ($is_addKeyWord) {
                $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $textStr);
            }
            // 加入匿名
            if ($is_addAnonymous) {
                $text .= $this->addAnonymous($manuscript);
            }
            //加入自定义尾部内容
            if ($is_addTailContent) {
                $text .= $this->addTailContent($botInfo->tail_content);
            }
        }

        switch ($objectType) {
            case 'text':
                $params['text'] = $text;

                //消息预览
                if ($message['disable_message_preview'] != 1) {
                    $params['disable_web_page_preview'] = true;
                }
                break;
            case 'photo':
                $file_id = $message['photo'][0]['file_id'];
                $params['photo'] = $file_id;
                $params['caption'] = $text;
                $method = 'sendPhoto';
                break;
            case 'video':
                $file_id = $message['video']['file_id'];
                $duration = $message['video']['duration'];
                $width = $message['video']['width'];
                $height = $message['video']['height'];

                $params['video'] = $file_id;
                $params['duration'] = $duration;
                $params['width'] = $width;
                $params['height'] = $height;
                $params['caption'] = $text;
                $method = 'sendVideo';
                break;
            case 'audio':
                $file_id = $message['audio']['file_id'];
                $duration = $message['audio']['duration'];
                $title = $message['audio']['file_name'];

                $params['audio'] = $file_id;
                $params['duration'] = $duration;
                $params['title'] = $title;
                $params['caption'] = $text;
                $method = 'sendAudio';
                break;
            case 'media_group_photo':
            case 'media_group_video':
                foreach ($message['media_group'] as $key => $item) {
                    $temp_array = [];
                    if (isset($item['photo'])) {
                        $temp_array = [
                            'type' => 'photo',
                            'media' => $item['photo'][0]['file_id'],
                        ];
                    }
                    if (isset($item['video'])) {
                        $temp_array = [
                            'type' => 'video',
                            'media' => $item['video']['file_id'],
                            'duration' => $item['video']['duration'],
                            'width' => $item['video']['width'],
                            'height' => $item['video']['height'],
                        ];
                    }
                    if (!empty($item['caption'] ?? '')) {
                        $text .= $item['caption'] ?? '';
                        //自动关键词
                        if ($is_addKeyWord) {
                            $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $item['caption']);
                        }
                        // 加入匿名
                        if ($is_addAnonymous) {
                            $text .= $this->addAnonymous($manuscript);
                        }
                        //加入自定义尾部内容
                        if ($is_addTailContent) {
                            $text .= $this->addTailContent($botInfo->tail_content);
                        }
                        if (!empty($custom_tail_content)) {
                            $text .= $custom_tail_content;
                        }
                        $temp_array['caption'] = $text;
                        $temp_array['parse_mode'] = 'HTML';
                    }
                    $media[] = $temp_array;
                }
                array_filter($media);
                $params['media'] = json_encode($media);
                $method = 'sendMediaGroup';
                $isReviewGroupText = '收到包含多张图片/视频的提交 👆';
                break;
            case 'media_group_audio':
                if (isset($message['text'])) {
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
                    $isReviewGroupText = '收到包含多个音频的提交 👆';
                } else {
                    $media = [];
                    foreach ($message as $key => $item) {
                        $temp_array = [
                            'type' => 'audio',
                            'media' => $item['audio']['file_id'],
                            'title' => $item['audio']['file_name'],
                            'duration' => $item['audio']['duration'],
                        ];
                        if (!empty($item['caption'] ?? '')) {
                            $text .= $item['caption'] ?? '';
                            //自动关键词
                            if ($is_addKeyWord) {
                                $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $item['caption']);
                            }
                            // 加入匿名
                            if ($is_addAnonymous) {
                                $text .= $this->addAnonymous($manuscript);
                            }
                            //加入自定义尾部内容
                            if ($is_addTailContent) {
                                $text .= $this->addTailContent($botInfo->tail_content);
                            }
                            if (!empty($custom_tail_content)) {
                                $text .= $custom_tail_content;
                            }
                            $temp_array['caption'] = $text;
                            $temp_array['parse_mode'] = 'HTML';
                        }
                        $media[] = $temp_array;
                    }
                    $params['media'] = json_encode($media);
                    $method = 'sendMediaGroup';
                }
                break;
            default:
                return 'error';
        }

        if (!empty($params['text'])) {
            if (!empty($custom_tail_content)) {
                $text .= $custom_tail_content;
            }
            $params['text'] = $text;
        } elseif (!empty($params['caption'])) {
            if (!empty($custom_tail_content)) {
                $text .= $custom_tail_content;
            }
            $params['caption'] = $text;
        }

        //静默发送
        if ($isChannel && $message['disable_notification'] == 1) {
            $params['disable_notification'] = true;
        }

        if ($objectType === 'media_group_audio') {
            $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);
        }
        //多媒体
        if ($isReviewGroup && in_array($objectType, ['media_group_photo', 'media_group_video', 'media_group_audio'])) {
            $mediaResult = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                'chat_id' => $chatId,
                'media' => json_encode($media),
            ], true);
            if ($mediaResult === "error") {
                return "error";
            }
            $result = $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => $isReviewGroupText,
                'reply_to_message_id' => $mediaResult[0]['message_id'],
                'parse_mode' => 'HTML',
                'reply_markup' => $inline_keyboard,
            ], $isReturnTelegramMessage);
        } else {
            $result = $this->sendTelegramMessage($telegram, $method, $params, $isReturnTelegramMessage);
        }
        if ($isReturnText) {
            return $textStr;
        }

        return $result;
    }

    /**
     * 记录投稿、投诉、意见反馈等文本消息
     *
     * @param string $cacheKey 缓存key
     * @param array $reply_markup 回复键盘
     * @param string $text_1 第一次记录的提示语
     * @param string $text_2 后续记录的提示语
     */
    public function updateByText(Api $telegram, $botInfo, mixed $chatId, mixed $messageId, $message, string $cacheKey, array $reply_markup, string $text_1, string $text_2): mixed
    {
        $cacheTag = Cache::tags($cacheKey);
        $text = $cacheTag->get('text') ? $text_2 : $text_1;
        $messageCacheData = preprocessMessageText($message, $botInfo);

        if ($messageCacheData === 'error') {
            return 'error';
        }

        Cache::tags($cacheKey)->put('text', $messageCacheData, now()->addDay());
        Cache::tags($cacheKey)->put('objectType', 'text', now()->addDay());

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($reply_markup),
        ]);
    }

    /**
     * 记录投稿、投诉、意见反馈等多媒体消息
     *
     * @param string $cacheKey 缓存key
     * @param array $reply_markup 回复键盘
     * @param string $text_1 第一次记录的提示语
     * @param string $text_2 后续记录的提示语
     */
    public function updateByMedia(Api $telegram, $botInfo, mixed $chatId, mixed $messageId, $message, $type, string $cacheKey, array $reply_markup, string $text_1, string $text_2): mixed
    {
        $cacheTag = Cache::tags($cacheKey);
        $media_group_id = $message->media_group_id ?? '';
        $objectType = $media_group_id ? 'media_group_' . $type : $type;

        $messageCacheData = preprocessMessageCaption($message, $botInfo);
        if ($messageCacheData === 'error') {
            return 'error';
        }
        if ($media_group_id) {
            $cacheKeyGroup = 'media_group';
            $cacheKeyGroupId = 'media_group:' . $media_group_id;

            $messageCache = $cacheTag->get($cacheKeyGroupId, []);
            $messageCache['media_group'][] = $messageCacheData;
            $text = count($messageCache) > 1 ? $text_2 : $text_1;

            $cacheTag->put($cacheKeyGroup, $media_group_id, now()->addDay());
            $cacheTag->put($cacheKeyGroupId, $messageCache, now()->addDay());
        } else {
            $cacheKeyByType = $type;
            $text = $cacheTag->has($cacheKeyByType) ? $text_2 : $text_1;
            $cacheTag->put($cacheKeyByType, $messageCacheData, now()->addDay());
        }
        Cache::tags($cacheKey)->put('objectType', $objectType, now()->addDay());

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($reply_markup),
        ]);
    }

    /**
     * 添加自动关键词
     */
    private function addKeyWord($is_auto_keyword, $keyword, $botId, $text): string
    {
        if (empty($keyword)) {
            return '';
        }
        //将关键词转换为数组，按行分割
        $keyword = preg_split('/\r\n|\n|\r/', $keyword);
        if (empty($text)) {
            return '';
        }
        if ($is_auto_keyword == 1) {
            $lexiconPath = null;
            //检查是否有词库
            if (Storage::exists("public/lexicon_{$botId}.txt")) {
                $lexiconPath = storage_path("app/public/lexicon_{$botId}.txt");
            }

            //分词
            $quickCut = quickCut($text, $lexiconPath);
            $keywords = [];
            foreach ($quickCut as $item) {
                if (in_array($item, $keyword)) {
                    $keywords[] = $item;
                }
            }
            //去除重复
            $keywords = array_unique($keywords);
            //拼接关键词
            if (!empty($keywords)) {
                $textContent = get_config('submission.channel_keywords');
                foreach ($keywords as $item) {
                    $textContent .= "#{$item} ";
                }

                return $textContent;
            }
        }

        return '';
    }

    /**
     * 添加匿名或投稿人
     */
    private function addAnonymous($manuscript): string
    {
        if (!empty($manuscript)) {
            if ($manuscript->is_anonymous === 1) {
                $text = get_config('submission.channel_anonymous');
            } else {
                $text = str(get_config('submission.channel_anonymous_no'))->swap([
                    '{posted_by}' => get_posted_by($manuscript->posted_by),
                ]);
            }

            return $text;
        }

        return '';
    }

    /**
     * 添加自定义尾部内容
     */
    private function addTailContent($tail_content): string
    {
        if (!empty($tail_content)) {
            return PHP_EOL . PHP_EOL . $tail_content;
        }

        return '';
    }

    /**
     * 添加审核结束文本
     */
    public function addReviewEndText($approved, $one_approved, $reject, $one_reject): string
    {
        $text = "\r\n\r\n ------------------- \r\n\r\n";

        $text .= '审核通过人员：';
        if (empty($approved) || count($approved) <= 0) {
            $text .= '无';
        } else {
            foreach ($approved as $approved_val) {
                $text .= "\r\n <code>" . get_posted_by($approved_val) . ' </code>';
            }
        }

        if (!empty($one_approved)) {
            $text .= "\r\n\r\n 快速审核通过人员：";
            $text .= "\r\n <code>" . get_posted_by($one_approved) . ' </code>';
        }

        $text .= "\r\n\r\n审核拒绝人员：";
        if (empty($reject) || count($reject) <= 0) {
            $text .= '无';
        } else {
            foreach ($reject as $reject_val) {
                $text .= "\r\n <code>" . get_posted_by($reject_val) . ' </code>';
            }
        }

        if (!empty($one_reject)) {
            $text .= "\r\n\r\n 快速审核拒绝人员：";
            $text .= "\r\n <code>" . get_posted_by($one_reject) . ' </code>';
        }

        $text .= "\r\n\r\n 审核通过时间：" . date('Y-m-d H:i:s', time());

        return $text;
    }

    /**
     * 发送消息
     */
    public function sendTelegramMessage($telegram, string $method, array $params, bool $isReturnTelegramMessage = false): mixed
    {
        foreach ($params as $key => $value) {
            if (empty($value)) {
                unset($params[$key]);
            }
        }
        try {

            if ($isReturnTelegramMessage) {
                return $telegram->$method($params);
            }
            $telegram->$method($params);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error('发送类型：' . $method);
            Log::error('发送参数：' . json_encode($params));
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
