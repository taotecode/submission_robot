<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\InlineKeyBoardData;
use App\Enums\KeyBoardData;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Manuscript;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use lucadevelop\TelegramEntitiesDecoder\EntityDecoder;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait SendTelegramMessageService
{
    public function sendPreviewMessage(
        Api         $telegram, $botInfo, string $chatId, array $message, string $objectType,
        bool        $is_addKeyWord = true, bool $is_addAnonymous = true, bool $is_addTailContent = true,
        string|null $custom_header_content = null, string|null $custom_tail_content = null
    ): mixed
    {
        return $this->objectTypeHandle(
            $telegram, $botInfo, $chatId, $objectType, $message, null, false, true,
            false, null, $is_addKeyWord, $is_addAnonymous, $is_addTailContent,
            $custom_header_content, $custom_tail_content
        );
    }

    /**
     * 发送审核群消息
     * @param Api $telegram
     * @param $botInfo
     * @param $message
     * @param $objectType
     * @param $manuscriptId
     * @param null $inline_keyboard
     * @return mixed
     */
    public function sendGroupMessage(
        Api $telegram, $botInfo, $message, $objectType, $manuscriptId,
            $inline_keyboard = null, $inline_keyboard_enums = null,
        bool        $is_addKeyWord = true, bool $is_addAnonymous = true, bool $is_addTailContent = true,
        string|null $custom_header_content = null, string|null $custom_tail_content = null
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
            $telegram, $botInfo, $chatId, $objectType, $message, $inline_keyboard, true, true,
            false, null, $is_addKeyWord, $is_addAnonymous, $is_addTailContent,
            $custom_header_content, $custom_tail_content
        );
    }

    /**
     * 发送审核群消息
     * @param Api $telegram
     * @param $botInfo
     * @param $manuscript
     * @param $channel
     * @return mixed
     */
    public function sendGroupMessageWhiteUser(Api $telegram, $botInfo, $manuscript, $channel): mixed
    {
        if (!empty($botInfo->review_group->name)) {
            $chatId = '@' . $botInfo->review_group->name;
        } else {
            $chatId = $botInfo->review_group->group_id;
        }

        $inline_keyboard = InlineKeyBoardData::$WHITE_LIST_USER_SUBMISSION;
        $inline_keyboard['inline_keyboard'][0][0]['url'] .= $botInfo->channel->name . "/" . $manuscript->message_id;
        $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscript->id";

        $username = get_posted_by($manuscript->posted_by);

        $text = "白名单用户<b>【 {$username} 】</b>的投稿";
        if (empty($manuscript->text)) {
            $text .= "已自动通过审核。";
        } else {
            $text .= "<a href='https://t.me/" . $channel->name . "/" . $manuscript->message_id . "'>“ " . get_text_title($manuscript->text) . " ”</a> 已自动通过审核。";
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
     * @param Api $telegram
     * @param $botInfo
     * @param Manuscript $manuscript
     * @return mixed
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
     * @param $message
     * @param array|null $inline_keyboard 按键
     * @param bool $isReviewGroup 是否是审核群
     * @param bool $isReturnText 是否返回文本
     * @param bool $isReturnTelegramMessage
     * @param null $manuscript 投稿信息
     * @param bool $is_addKeyWord
     * @param bool $is_addAnonymous
     * @param bool $is_addTailContent
     * @param string|null $custom_header_content
     * @param string|null $custom_tail_content
     * @return mixed|string
     */
    private function objectTypeHandle(
        Api         $telegram, mixed $botInfo, array|int|string $chatId, string $objectType, $message, ?array $inline_keyboard = null,
        bool        $isReviewGroup = false, bool $isReturnText = false, bool $isReturnTelegramMessage = false,
                    $manuscript = null, bool $is_addKeyWord = true, bool $is_addAnonymous = true, bool $is_addTailContent = true,
        string|null $custom_header_content = null, string|null $custom_tail_content = null
    ): mixed
    {
        if (empty($inline_keyboard)) {
            $inline_keyboard = null;
        } else {
            $inline_keyboard = json_encode($inline_keyboard);
        }

        if ($is_addTailContent){
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
                foreach ($message as $key => $item) {
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
        }elseif (!empty($params['caption'])) {
            if (!empty($custom_tail_content)) {
                $text .= $custom_tail_content;
            }
            $params['caption'] = $text;
        }

        if ($objectType === 'media_group_audio') {
            $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);
        }
        if ($isReviewGroup&&in_array($objectType, ['media_group_photo', 'media_group_video', 'media_group_audio'])) {
            $mediaResult = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                'chat_id' => $chatId,
                'media' => json_encode($media),
            ], true);
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
     * @param Api $telegram
     * @param mixed $chatId
     * @param mixed $messageId
     * @param $message
     * @param string $cacheKey 缓存key
     * @param array $reply_markup 回复键盘
     * @param string $text_1 第一次记录的提示语
     * @param string $text_2 后续记录的提示语
     * @return mixed
     */
    public function updateByText(Api $telegram,$botInfo, mixed $chatId, mixed $messageId, $message, string $cacheKey, array $reply_markup, string $text_1, string $text_2): mixed
    {
        if (empty(Cache::tags($cacheKey)->get('text'))) {
            $text = $text_1;
        } else {
            $text = $text_2;
        }

        $messageCacheData = $message->toArray();

        if (!empty($messageCacheData['text'])&&$botInfo->is_message_text_preprocessing==1) {
            $entity_decoder = new EntityDecoder('HTML');
            //消息文字预处理
//            $messageCacheData['text'] = htmlspecialchars($messageCacheData['text'], ENT_QUOTES, 'UTF-8');
            try {
                if (!is_object($message)){
                    $messageCacheDataTmp=collect($message);
                }else{
                    $messageCacheDataTmp=$message;
                }
                $messageCacheData['text'] = $entity_decoder->decode($messageCacheDataTmp);
            } catch (Exception $e) {
                Log::error('消息文字预处理失败：' . $e->getMessage());
                return 'error';
            }
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
     * @param Api $telegram
     * @param mixed $chatId
     * @param mixed $messageId
     * @param $message
     * @param $type
     * @param string $cacheKey 缓存key
     * @param array $reply_markup 回复键盘
     * @param string $text_1 第一次记录的提示语
     * @param string $text_2 后续记录的提示语
     * @return mixed
     */
    public function updateByMedia(Api $telegram,$botInfo, mixed $chatId, mixed $messageId, $message, $type, string $cacheKey, array $reply_markup, string $text_1, string $text_2): mixed
    {
        $media_group_id = $message->media_group_id ?? '';
        $cacheKeyByType = $type;
        $cacheKeyGroup = 'media_group';
        $cacheKeyGroupId = 'media_group' . ':' . $media_group_id;
        $objectType = $type;

        $entity_decoder = new EntityDecoder('HTML');

        if (!empty($media_group_id)) {
            $objectType = 'media_group_' . $type;

            $messageCacheData = $message->toArray();

            if (!empty($messageCacheData['caption'])&&$botInfo->is_message_text_preprocessing==1) {
                //消息文字预处理
//                $messageCacheData['caption'] = htmlspecialchars($messageCacheData['caption'], ENT_QUOTES, 'UTF-8');
                try {
                    if (!is_object($message)){
                        $messageCacheDataTmp=collect($message);
                    }else{
                        $messageCacheDataTmp=$message;
                    }
                    $messageCacheData['caption'] = $entity_decoder->decode($messageCacheDataTmp);
                } catch (Exception $e) {
                    Log::error('消息文字预处理失败：' . $e->getMessage());
                    return 'error';
                }
            }

            //存入缓存，等待所有图片接收完毕
            if (Cache::tags($cacheKey)->has($cacheKeyGroupId)) {
                //如果存在缓存，则将消息合并
                $messageCache = Cache::tags($cacheKey)->get($cacheKeyGroupId);
                $messageCache[] = $messageCacheData;
                $text = $text_2;
            } else {
                $messageCache = [$messageCacheData];
                $text = $text_1;
            }
            Cache::tags($cacheKey)->put($cacheKeyGroup, $media_group_id, now()->addDay());
            Cache::tags($cacheKey)->put($cacheKeyGroupId, $messageCache, now()->addDay());
        } else {

            $messageCacheData = $message->toArray();

            if (!empty($messageCacheData['caption'])&&$botInfo->is_message_text_preprocessing==1) {
                //消息文字预处理
//                $messageCacheData['caption'] = htmlspecialchars($messageCacheData['caption'], ENT_QUOTES, 'UTF-8');
                try {
                    if (!is_object($message)){
                        $messageCacheDataTmp=collect($message);
                    }else{
                        $messageCacheDataTmp=$message;
                    }
                    $messageCacheData['caption'] = $entity_decoder->decode($messageCacheDataTmp);
                } catch (Exception $e) {
                    Log::error('消息文字预处理失败：' . $e->getMessage());
                    return 'error';
                }
            }

            if (Cache::tags($cacheKey)->has($cacheKeyByType)) {
                $text = $text_2;
            } else {
                $text = $text_1;
            }
            Cache::tags($cacheKey)->put($cacheKeyByType, $messageCacheData, now()->addDay());
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
     * @param $is_auto_keyword
     * @param $keyword
     * @param $botId
     * @param $text
     * @return string
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
                $textContent = PHP_EOL . PHP_EOL . '关键词：';
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
     * @param $manuscript
     * @return string
     */
    private function addAnonymous($manuscript): string
    {
        if (!empty($manuscript)) {
            if ($manuscript->is_anonymous === 1) {
                $text = PHP_EOL . PHP_EOL . '匿名投稿';
            } else {
                $text = PHP_EOL . PHP_EOL . '投稿人：' . get_posted_by($manuscript->posted_by);
            }

            return $text;
        }

        return '';
    }

    /**
     * 添加自定义尾部内容
     * @param $tail_content
     * @return string
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
     * @param $approved
     * @param $one_approved
     * @param $reject
     * @param $one_reject
     * @return string
     */
    public function addReviewEndText($approved, $one_approved, $reject, $one_reject): string
    {
        $text = "\r\n ------------------- \r\n";
        $text .= "审核通过人员：";

        foreach ($approved as $approved_val) {
            $text .= "\r\n <code>" . get_posted_by($approved_val) . " </code>";
        }

        if (!empty($one_approved)) {
            $text .= "\r\n <code>" . get_posted_by($one_approved) . " </code>";
        }

        $text .= "\r\n审核拒绝人员：";

        foreach ($reject as $reject_val) {
            $text .= "\r\n <code>" . get_posted_by($reject_val) . " </code>";
        }

        if (!empty($one_reject)) {
            $text .= "\r\n <code>" . get_posted_by($one_reject) . " </code>";
        }

        $text .= "\r\n审核通过时间：" . date('Y-m-d H:i:s', time());

        return $text;
    }

    /**
     * 发送消息
     * @param $telegram
     * @param string $method
     * @param array $params
     * @param bool $isReturnTelegramMessage
     * @return mixed
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
