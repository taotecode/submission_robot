<?php

namespace App\Services;

use App\Enums\KeyBoardData;
use App\Models\Manuscript;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;

class SubmissionService
{
    use SendPreviewMessageService;
    use SendGroupMessageService;

    public Manuscript $manuscriptModel;

    public function __construct(Manuscript $manuscriptModel)
    {
        $this->manuscriptModel = $manuscriptModel;
    }

    public string $cacheTag = 'start_submission';

    public function index($botInfo, Update $updateData, Api $telegram)
    {
        $chat = $updateData->getChat();
        $chatId = $chat->id;
        $message = $updateData->getMessage();
        $messageId = $message->messageId;
        $objectType = $message->objectType();
        $forwardFrom = $message->forwardFrom ?? '';
        $forwardSignature = $message->forwardSignature ?? '';
        //        dd($message->toArray(),$objectType);

        switch ($objectType) {
            case 'text':
                if ($message->text == '开始投稿') {
                    return $this->start($telegram, $chatId, $chat, get_config('submission.start'));
                }
                if (! Cache::tags($this->cacheTag.'.'.$chatId)->has($chatId)) {
                    return $this->error_for_text($telegram, $chatId, $messageId);
                }
                /*
                if ($message->text == "意见反馈") {
                    return $this->feedback($telegram, $chatId);
                }
                if ($message->text == "帮助中心") {
                    return $this->help($telegram, $chatId);
                }*/
                if ($message->text == '取消投稿') {
                    return $this->cancel($telegram, $chatId);
                }
                if ($message->text == '重新开始') {
                    return $this->start($telegram, $chatId, $chat, get_config('submission.restart'));
                }
                if ($message->text == '结束发送') {
                    return $this->end($telegram, $chatId, $botInfo);
                }
                if ($message->text == '确认投稿（公开）') {
                    return $this->confirm($telegram, $chatId, $chat, $botInfo, 0);
                }
                if ($message->text == '确认投稿（匿名）') {
                    return $this->confirm($telegram, $chatId, $chat, $botInfo, 1);
                }
                //进入纯文本投稿
                $this->updateByText($telegram, $chatId, $messageId, $message);
                break;
            case 'photo':
            case 'video':
            case 'audio':
                if (! Cache::tags($this->cacheTag.'.'.$chatId)->has($chatId)) {
                    return $this->error_for_text($telegram, $chatId, $messageId);
                }
                $this->media($telegram, $chatId, $messageId, $message, $objectType);
                break;
        }

    }

    /**
     * 开始API并使用给定的参数。
     *
     * @param  Api  $telegram API对象。
     * @param  string  $chatId 聊天ID。
     * @param  string  $text 要发送的文本消息。默认为"请直接发送您要投稿的内容\r\n发送完毕后，请点击下方的 “结束发送” 按钮。"
     * @return string API调用的结果。可能的值为"ok"或"error"。
     */
    private function start(
        Api $telegram,
        string $chatId,
        Collection $chat,
        string $text = "请直接发送您要投稿的内容\r\n发送完毕后，请点击下方的 “结束发送” 按钮。",
    ): string {
        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
            ]);

            Cache::tags($this->cacheTag.'.'.$chatId)->flush();

            Cache::tags($this->cacheTag.'.'.$chatId)->put($chatId, $chat->toArray(), now()->addDay());

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    /**
     * 取消投稿。
     *
     * @param  Api  $telegram Telegram API对象。
     * @param  string  $chatId 聊天ID。
     * @return string 取消投稿的结果：如果成功则为'ok'，否则为'error'。
     */
    private function cancel(Api $telegram, string $chatId): string
    {
        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => get_config('submission.cancel'),
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => json_encode(KeyBoardData::START),
            ]);
            Cache::tags($this->cacheTag.'.'.$chatId)->flush();

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    /**
     * 向Telegram聊天发送文本的错误消息。
     *
     * @param  Api  $telegram Telegram API实例。
     * @param  string  $chatId 要发送消息的聊天ID。
     * @param  string  $messageId 要回复的消息ID。
     * @return string 操作的结果（'ok'或'error'）。
     */
    private function error_for_text(Api $telegram, string $chatId, string $messageId): string
    {
        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.error_for_text'),
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => json_encode(KeyBoardData::START),
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    private function media(Api $telegram, $chatId, $messageId, Collection $message, $type)
    {
        $media_group_id = $message->media_group_id ?? '';
        $cacheKey = $type;
        $cacheKeyGroup = $type.':media_group_'.$type;
        $cacheKeyGroupId = $type.':media_group_'.$type.':'.$media_group_id;
        $objectType = $type;
        if (! empty($media_group_id)) {
            $objectType = 'media_group_'.$type;
        }
        if (! empty($media_group_id)) {
            //存入缓存，等待所有图片接收完毕
            if (Cache::tags($this->cacheTag.'.'.$chatId)->has($cacheKeyGroupId)) {
                //如果存在缓存，则将消息合并
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get($cacheKeyGroupId);
                $messageCache[] = $message->toArray();
                $text = get_config('submission.start_update_text_tips');
            } else {
                $messageCache = [$message->toArray()];
                $text = get_config('submission.start_text_tips');
            }
            Cache::tags($this->cacheTag.'.'.$chatId)->put($cacheKeyGroup, $media_group_id, now()->addDay());
            Cache::tags($this->cacheTag.'.'.$chatId)->put($cacheKeyGroupId, $messageCache, now()->addDay());
            Cache::tags($this->cacheTag.'.'.$chatId)->put('objectType', $objectType, now()->addDay());
        } else {
            if (Cache::tags($this->cacheTag.'.'.$chatId)->has($cacheKey)) {
                $text = get_config('submission.start_update_text_tips');
            } else {
                $text = get_config('submission.start_text_tips');
            }
            Cache::tags($this->cacheTag.'.'.$chatId)->put($cacheKey, $message->toArray(), now()->addDay());
            Cache::tags($this->cacheTag.'.'.$chatId)->put('objectType', $objectType, now()->addDay());
        }

        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'MarkdownV2',
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    /**
     * 更新API中的指定消息。
     *
     * @param  Api  $telegram API对象。
     * @param  string  $chatId 聊天ID。
     * @param  string  $messageId 消息ID。
     * @param  Collection  $message 要更新的消息。
     * @return string 更新的状态。
     */
    private function updateByText(
        Api $telegram,
        string $chatId,
        string $messageId,
        Collection $message
    ): string {
        if (empty(Cache::tags($this->cacheTag.'.'.$chatId)->get('text'))) {
            $text = get_config('submission.start_text_tips');
        } else {
            $text = get_config('submission.start_update_text_tips');
        }
        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'MarkdownV2',
            ]);
            Cache::tags($this->cacheTag.'.'.$chatId)->put('text', $message->toArray(), now()->addDay());
            Cache::tags($this->cacheTag.'.'.$chatId)->put('objectType', 'text', now()->addDay());

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    private function end(Api $telegram, $chatId, $botInfo): string
    {
        $objectType = Cache::tags($this->cacheTag.'.'.$chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];

        switch ($objectType) {
            case 'text':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('text');
                $messageId = $messageCache['message_id'] ?? '';
                if (!isset($messageCache['text']) || empty($messageCache['text'])) {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'reply_to_message_id' => $messageId,
                        'text' => "您还没有输入任何内容，请重新输入！",
                        'parse_mode' => 'MarkdownV2',
                        'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
                    ]);
                    return 'ok';
                }
                break;
            case 'photo':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo');
                $messageId = $messageCache['message_id'] ?? '';
                break;
            case 'media_group_photo':
                $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo:media_group_photo');
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo:media_group_photo:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                break;
            case 'video':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('video');
                $messageId = $messageCache['message_id'] ?? '';
                break;
            case 'media_group_video':
                $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('video:media_group_video');
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('video:media_group_video:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                break;
            case 'audio':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio');
                $messageId = $messageCache['message_id'] ?? '';
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags($this->cacheTag.'.'.$chatId)->has('text')) {
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio');
                    $audioMessageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio:'.$media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                } else {
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio');
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio:'.$media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                }
                break;
        }

        try {

            $this->sendPreviewMessage($telegram, $botInfo, $chatId, $messageCache, $objectType);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.preview_tips'),
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => json_encode(KeyBoardData::END_SUBMISSION),
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    private function confirm(Api $telegram, $chatId, $chat, $botInfo, $is_anonymous)
    {
        $objectType = Cache::tags($this->cacheTag.'.'.$chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];

        switch ($objectType) {
            case 'text':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('text');
                $messageId = $messageCache['message_id'] ?? '';
                break;
            case 'photo':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo');
                $messageId = $messageCache['message_id'] ?? '';
                break;
            case 'media_group_photo':
                $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo:media_group_photo');
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo:media_group_photo:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                break;
            case 'video':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('video');
                $messageId = $messageCache['message_id'] ?? '';
                break;
            case 'media_group_video':
                $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('video:media_group_video');
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('video:media_group_video:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                break;
            case 'audio':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio');
                $messageId = $messageCache['message_id'] ?? '';
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags($this->cacheTag.'.'.$chatId)->has('text')) {
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio');
                    $audioMessageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio:'.$media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                } else {
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio');
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio:media_group_audio:'.$media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                }
                break;
        }
        try {

            //将稿件信息存入数据库中
            $sqlData = [
                'type' => $objectType,
                'text' => '',
                'posted_by' => $chat->toArray(),
                'is_anonymous' => $is_anonymous,
                'data' => $messageCache,
                'appendix' => [],
                'approved' => [],
                'reject' => [],
                'one_approved' => [],
                'one_reject' => [],
                'status' => 0,
            ];

            $manuscript = $this->manuscriptModel->create($sqlData);
            // 发送消息到审核群组
            $text = $this->sendGroupMessage($telegram, $botInfo, $messageCache, $objectType, $manuscript->id);
            //            $text=$this->sendGroupMessage($telegram,$botInfo,$messageCache,$objectType,1);
            $replaced = Str::replace('\r\n', PHP_EOL, $text);
            $limitedString = Str::limit($replaced);
            $firstLine = Str::before($limitedString, PHP_EOL);

            $manuscript->text = $firstLine;
            $manuscript->save();

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.confirm'),
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => json_encode(KeyBoardData::START),
            ]);
            Cache::tags($this->cacheTag.'.'.$chatId)->flush();

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
