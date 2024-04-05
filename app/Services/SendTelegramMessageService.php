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
    public function sendGroupMessage(Api $telegram, $botInfo, $message, $objectType, $manuscriptId,$inline_keyboard=null): mixed
    {
        if (!empty($botInfo->review_group->name)) {
            $chatId = '@' . $botInfo->review_group->name;
        } else {
            $chatId = $botInfo->review_group->group_id;
        }

        $review_num = $botInfo->review_num;

        if ($inline_keyboard===null){
            $inline_keyboard = KeyBoardData::REVIEW_GROUP;

            $inline_keyboard['inline_keyboard'][0][0]['text'] .= "(0/$review_num)";
            $inline_keyboard['inline_keyboard'][0][0]['callback_data'] .= ":$manuscriptId";

            $inline_keyboard['inline_keyboard'][0][1]['text'] .= "(0/$review_num)";
            $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscriptId";

            $inline_keyboard['inline_keyboard'][0][2]['callback_data'] .= ":$manuscriptId";

            $inline_keyboard['inline_keyboard'][1][0]['callback_data'] .= ":$manuscriptId";
            $inline_keyboard['inline_keyboard'][1][1]['callback_data'] .= ":$manuscriptId";
        }

        return $this->objectTypeHandle($telegram, $botInfo, $chatId, $objectType, $message, $inline_keyboard, true, true);
    }

    public function sendGroupMessageWhiteUser(Api $telegram, $botInfo, $manuscript)
    {
        if (!empty($botInfo->review_group->name)) {
            $chatId = '@' . $botInfo->review_group->name;
        } else {
            $chatId = $botInfo->review_group->group_id;
        }

        $inline_keyboard = KeyBoardData::WHITE_LIST_USER_SUBMISSION;
        $inline_keyboard['inline_keyboard'][0][0]['url'] .= $botInfo->channel->name . "/" . $manuscript->message_id;
        $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscript->id";

        $username = get_posted_by($manuscript->posted_by);

        $text="白名单用户<b>【 {$username} 】</b>的投稿";
        if (empty($manuscript->text)){
            $text .= "已自动通过审核。";
        } else {
            $text .= "“ ".$manuscript->text." ” 已自动通过审核。";
        }

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inline_keyboard),
        ]);
    }

    public function sendChannelMessage(Api $telegram, $botInfo, Manuscript $manuscript): mixed
    {

        $message = $manuscript->data;

        $objectType = $manuscript->type;

        //频道ID
        if (!empty($botInfo->channel_id)) {
            $chatId = '@' . $botInfo->channel->name;
        }else{
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
     * @param string|int|array $chatId 频道id或者频道ID数组或者用户id
     * @param string $objectType 类型
     * @param $message
     * @param array|null $inline_keyboard 按键
     * @param bool $isReviewGroup 是否是审核群
     * @param bool $isReturnText 是否返回文本
     * @param bool $isReturnTelegramMessage
     * @param null $manuscript 投稿信息
     * @return mixed|string
     */
    private function objectTypeHandle(Api $telegram, $botInfo, $chatId, $objectType, $message, ?array $inline_keyboard = null, bool $isReviewGroup = false, bool $isReturnText = false, bool $isReturnTelegramMessage=false, $manuscript = null): mixed
    {
        if (empty($inline_keyboard)) {
            $inline_keyboard = null;
        } else {
            $inline_keyboard = json_encode($inline_keyboard);
        }

        $tail_content_button = $botInfo->tail_content_button;
        if (!empty($tail_content_button) && !$isReviewGroup) {
            $inline_keyboard = json_encode([
                'inline_keyboard' => $tail_content_button,
            ]);
        }

        switch ($objectType) {
            case 'text':
                $text = $message['text'] ?? '';
                //自动关键词
                $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $text);
                // 加入匿名
                $text .= $this->addAnonymous($manuscript);
                //加入自定义尾部内容
                $text .= $this->addTailContent($botInfo->tail_content);
                $result = $this->sendTelegramMessage($telegram, 'sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ], $isReturnTelegramMessage);
                if ($isReturnText) {
                    return $text;
                }
                return $result;
            case 'photo':
                $file_id = $message['photo'][0]['file_id'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $caption);
                // 加入匿名
                $caption .= $this->addAnonymous($manuscript);
                //加入自定义尾部内容
                $caption .= $this->addTailContent($botInfo->tail_content);

                $result = $this->sendTelegramMessage($telegram, 'sendPhoto', [
                    'chat_id' => $chatId,
                    'photo' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ], $isReturnTelegramMessage);
                if ($isReturnText) {
                    return $caption;
                }
                return $result;
            case 'video':
                $file_id = $message['video']['file_id'];
                $duration = $message['video']['duration'];
                $width = $message['video']['width'];
                $height = $message['video']['height'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $caption);
                // 加入匿名
                $caption .= $this->addAnonymous($manuscript);
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
                ], $isReturnTelegramMessage);
                if ($isReturnText) {
                    return $caption;
                }
                return $result;
            case 'media_group_photo':
            case 'media_group_video':
                $media = [];
                $caption = '';
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
                        $caption = $item['caption'] ?? '';
                        //自动关键词
                        $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $caption);
                        // 加入匿名
                        $caption .= $this->addAnonymous($manuscript);
                        //加入自定义尾部内容
                        $caption .= $this->addTailContent($botInfo->tail_content);
                        $temp_array['caption'] = $caption;
                        $temp_array['parse_mode'] = 'HTML';
                    }
                    $media[] = $temp_array;
                }

                if ($isReviewGroup) {
                    $mediaResult = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ], true);
                    $result = $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '收到包含多张图片/视频的提交 👆',
                        'reply_to_message_id' => $mediaResult[0]['message_id'],
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ], $isReturnTelegramMessage);
                }else{
                    $result = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                        'chat_id' => $chatId,
                        'media' => json_encode($media),
                    ], $isReturnTelegramMessage);
                }
                if ($isReturnText) {
                    return $caption;
                }
                return $result;
            case 'audio':
                $file_id = $message['audio']['file_id'];
                $duration = $message['audio']['duration'];
                $title = $message['audio']['file_name'];
                $caption = $message['caption'] ?? '';
                //自动关键词
                $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $caption);
                // 加入匿名
                $caption .= $this->addAnonymous($manuscript);
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
                ], $isReturnTelegramMessage);

                if ($isReturnText) {
                    return $caption;
                }
                return $result;
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
                    $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $text);
                    // 加入匿名
                    $text .= $this->addAnonymous($manuscript);
                    //加入自定义尾部内容
                    $text .= $this->addTailContent($botInfo->tail_content);
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $text,
                        'parse_mode' => 'HTML',
                    ]);



                    if ($isReviewGroup) {
                        $mediaResult = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ], true);
                        $result = $this->sendTelegramMessage($telegram, 'sendMessage', [
                            'chat_id' => $chatId,
                            'text' => '收到包含多个音频的提交 👆',
                            'reply_to_message_id' => $mediaResult[0]['message_id'],
                            'parse_mode' => 'HTML',
                            'reply_markup' => $inline_keyboard,
                        ], $isReturnTelegramMessage);
                    }else{
                        $result = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ], $isReturnTelegramMessage);
                    }

                    if ($isReturnText) {
                        return $text;
                    }
                    return $result;
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
                            $caption = $item['caption'] ?? '';
                            //自动关键词
                            $caption .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $caption);
                            // 加入匿名
                            $caption .= $this->addAnonymous($manuscript);
                            //加入自定义尾部内容
                            $caption .= $this->addTailContent($botInfo->tail_content);
                            $temp_array['caption'] = $caption;
                            $temp_array['parse_mode'] = 'HTML';
                        }
                        $media[] = $temp_array;
                    }

                    if ($isReviewGroup) {
                        $mediaResult = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ], true);
                        $result = $this->sendTelegramMessage($telegram, 'sendMessage', [
                            'chat_id' => $chatId,
                            'text' => '收到包含多个音频的提交 👆',
                            'reply_to_message_id' => $mediaResult[0]['message_id'],
                            'parse_mode' => 'HTML',
                            'reply_markup' => $inline_keyboard,
                        ], $isReturnTelegramMessage);
                    }else{
                        $result = $this->sendTelegramMessage($telegram, 'sendMediaGroup', [
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ], $isReturnTelegramMessage);
                    }
                    if ($isReturnText) {
                        return '';
                    }
                    return $result;
                }
                break;
            default:
                return 'error';
        }
    }

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

    private function addTailContent($tail_content): string
    {
        if (!empty($tail_content)) {
            return PHP_EOL . PHP_EOL . $tail_content;
        }

        return '';
    }

    public function addReviewEndText($approved,$one_approved,$reject,$one_reject): string
    {
        $text = "\r\n ------------------- \r\n";
        $text .= "审核通过人员：";

        foreach ($approved as $approved_val){
            $text .= "\r\n <code>".get_posted_by($approved_val)." </code>";
        }

        if (!empty($one_approved)){
            $text .= "\r\n <code>".get_posted_by($one_approved)." </code>";
        }

        $text .= "\r\n审核拒绝人员：";

        foreach ($reject as $reject_val){
            $text .= "\r\n <code>".get_posted_by($reject_val)." </code>";
        }

        if (!empty($one_reject)){
            $text .= "\r\n <code>".get_posted_by($one_reject)." </code>";
        }

        $text .= "\r\n审核通过时间：".date('Y-m-d H:i:s',time());

        return $text;
    }

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
