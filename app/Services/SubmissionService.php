<?php

namespace App\Services;

use App\Enums\KeyBoardData;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class SubmissionService
{
    //    use SendPreviewMessageService;
    //    use SendGroupMessageService;
    use SendTelegramMessageService;

    public Manuscript $manuscriptModel;

    public string $cacheTag = 'start_submission';

    public function __construct(Manuscript $manuscriptModel)
    {
        $this->manuscriptModel = $manuscriptModel;
    }

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
        Cache::tags($this->cacheTag.'.'.$chatId)->flush();
        Cache::tags($this->cacheTag.'.'.$chatId)->put($chatId, $chat->toArray(), now()->addDay());

        $submissionUser = (new SubmissionUser)->firstOrCreate([
            'userId' => $chatId,
        ], [
            'type' => 0,
            'userId' => $chatId,
            'name' => get_posted_by($chat->toArray()),
        ]);

        if ($submissionUser->type == 2) {
            Cache::tags($this->cacheTag.'.'.$chatId)->flush();

            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('submission.black_list'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::BLACKLIST_USER_DELETE),
            ]);
        }

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
        ]);
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
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('submission.error_for_text'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START),
        ]);
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
        Cache::tags($this->cacheTag.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('submission.cancel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START),
        ]);
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
                if (! isset($messageCache['text']) || empty($messageCache['text'])) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'reply_to_message_id' => $messageId,
                        'text' => get_config('submission.submission_is_empty'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
                    ]);

                    return 'ok';
                }
                break;
            case 'photo':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    ! isset($messageCache['photo'][0]['file_id']) || empty($messageCache['photo'][0]['file_id'])
                ) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'reply_to_message_id' => $messageId,
                        'text' => get_config('submission.submission_is_empty'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
                    ]);

                    return 'ok';
                }
                break;
            case 'video':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('video');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    ! isset($messageCache['video']['file_id']) || empty($messageCache['video']['file_id'])
                ) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'reply_to_message_id' => $messageId,
                        'text' => get_config('submission.submission_is_empty'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
                    ]);

                    return 'ok';
                }
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group');
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                if (
                    ! isset($messageCache[0]['photo'][0]['file_id']) && ! isset($messageCache[0]['video']['file_id'])
                ) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'reply_to_message_id' => $messageId,
                        'text' => get_config('submission.submission_is_empty'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
                    ]);

                    return 'ok';
                }
                break;
            case 'audio':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    ! isset($messageCache['audio']['file_id']) || empty($messageCache['audio']['file_id'])
                ) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'reply_to_message_id' => $messageId,
                        'text' => get_config('submission.submission_is_empty'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
                    ]);

                    return 'ok';
                }
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags($this->cacheTag.'.'.$chatId)->has('text')) {
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group');
                    $audioMessageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                } else {
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group');
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                }
                break;
            default:
                $this->sendTelegramMessage($telegram, 'sendMessage', [
                    'chat_id' => $chatId,
                    'reply_to_message_id' => $messageId,
                    'text' => get_config('submission.submission_is_empty'),
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
                ]);

                return 'ok';
        }

        $this->sendPreviewMessage($telegram, $botInfo, $chatId, $messageCache, $objectType);

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('submission.preview_tips'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::END_SUBMISSION),
        ]);
    }

    private function confirm(Api $telegram, $chatId, $chat, $botInfo, $is_anonymous): string
    {
        $objectType = Cache::tags($this->cacheTag.'.'.$chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $messageText = '';

        switch ($objectType) {
            case 'text':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('text');
                $messageId = $messageCache['message_id'] ?? '';
                $messageText = $messageCache['text'] ?? '';
                break;
            case 'photo':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('photo');
                $messageId = $messageCache['message_id'] ?? '';
                $messageText = $messageCache['caption'] ?? '';
                break;
            case 'video':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('video');
                $messageId = $messageCache['message_id'] ?? '';
                $messageText = $messageCache['caption'] ?? '';
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group');
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                foreach ($messageCache as $key => $value) {
                    $messageText .= $value['caption'] ?? '';
                }
                break;
            case 'audio':
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('audio');
                $messageId = $messageCache['message_id'] ?? '';
                $messageText = $messageCache['caption'] ?? '';
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags($this->cacheTag.'.'.$chatId)->has('text')) {
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group');
                    $audioMessageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                    $messageText = $messageCache['text']['text'] ?? '';
                } else {
                    $media_group_id = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group');
                    $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                    foreach ($messageCache as $key => $value) {
                        $messageText .= $value['caption'] ?? '';
                    }
                }
                break;
        }

        //检查投稿人是否已在数据库中
        $submissionUser = (new SubmissionUser)->firstOrCreate([
            'userId' => $chat->id,
        ], [
            'type' => 0,
            'userId' => $chat->id,
            'name' => get_posted_by($chat->toArray()),
        ]);

        //将稿件信息存入数据库中
        $sqlData = [
            'bot_id' => $botInfo->id,
            'type' => $objectType,
            'text' => '',
            'posted_by' => $chat->toArray(),
            'posted_by_id' => $submissionUser->id,
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

        //白名单用户直接发布
        if ($submissionUser->type == 1) {
            $replaced = Str::replace('\r\n', PHP_EOL, $messageText);
            $limitedString = Str::limit($replaced);
            $firstLine = Str::before($limitedString, PHP_EOL);

            $manuscript->text = $firstLine;
            $manuscript->status = 1;
            $channelMessageId = $this->sendChannelMessage($telegram, $botInfo, $manuscript);
            if (!$channelMessageId){
                return 'ok';
            }
            $manuscript->message_id = $channelMessageId['message_id']??null;
            $manuscript->save();
            Cache::tags($this->cacheTag.'.'.$chatId)->flush();

            $chatText=get_config('submission.confirm_white_list');

            if (empty($manuscript->text)){
                $chatText .= "\r\n\r\n稿件消息直达链接：<a href='https://t.me/" . $botInfo->channel->name . "/" . $manuscript->message_id . "'>点击查看</a>";
            } else {
                $chatText .= "\r\n\r\n稿件消息直达链接：<a href='https://t.me/" . $botInfo->channel->name . "/" . $manuscript->message_id . "'>" . $manuscript->text . "</a>";
            }

            $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => $chatText,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::START),
            ]);

            return $this->sendGroupMessageWhiteUser($telegram, $botInfo, $manuscript);
        }
        // 发送消息到审核群组
        $text = $this->sendGroupMessage($telegram, $botInfo, $messageCache, $objectType, $manuscript->id);
        //            $text=$this->sendGroupMessage($telegram,$botInfo,$messageCache,$objectType,1);
        $replaced = Str::replace('\r\n', PHP_EOL, $text);
        $limitedString = Str::limit($replaced);
        $firstLine = Str::before($limitedString, PHP_EOL);

        $manuscript->text = $firstLine;
        $manuscript->save();

        Cache::tags($this->cacheTag.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('submission.confirm'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START),
        ]);
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

        $messageCacheData = $message->toArray();

        if (! empty($messageCacheData['text'])) {
            //消息文字预处理
            $messageCacheData['text'] = htmlspecialchars($messageCacheData['text'], ENT_QUOTES, 'UTF-8');
        }

        Cache::tags($this->cacheTag.'.'.$chatId)->put('text', $messageCacheData, now()->addDay());
        Cache::tags($this->cacheTag.'.'.$chatId)->put('objectType', 'text', now()->addDay());

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
        ]);
    }

    private function media(Api $telegram, $chatId, $messageId, Collection $message, $type): string
    {
        $media_group_id = $message->media_group_id ?? '';
        $cacheKey = $type;
        $cacheKeyGroup = 'media_group';
        $cacheKeyGroupId = 'media_group'.':'.$media_group_id;
        $objectType = $type;
        if (! empty($media_group_id)) {
            $objectType = 'media_group_'.$type;

            $messageCacheData = $message->toArray();

            if (! empty($messageCacheData['caption'])) {
                //消息文字预处理
                $messageCacheData['caption'] = htmlspecialchars($messageCacheData['caption'], ENT_QUOTES, 'UTF-8');
            }

            //存入缓存，等待所有图片接收完毕
            if (Cache::tags($this->cacheTag.'.'.$chatId)->has($cacheKeyGroupId)) {
                //如果存在缓存，则将消息合并
                $messageCache = Cache::tags($this->cacheTag.'.'.$chatId)->get($cacheKeyGroupId);
                $messageCache[] = $messageCacheData;
                $text = get_config('submission.start_update_text_tips');
            } else {
                $messageCache = [$messageCacheData];
                $text = get_config('submission.start_text_tips');
            }
            Cache::tags($this->cacheTag.'.'.$chatId)->put($cacheKeyGroup, $media_group_id, now()->addDay());
            Cache::tags($this->cacheTag.'.'.$chatId)->put($cacheKeyGroupId, $messageCache, now()->addDay());
        } else {

            $messageCacheData = $message->toArray();

            if (! empty($messageCacheData['caption'])) {
                //消息文字预处理
                $messageCacheData['caption'] = htmlspecialchars($messageCacheData['caption'], ENT_QUOTES, 'UTF-8');
            }

            if (Cache::tags($this->cacheTag.'.'.$chatId)->has($cacheKey)) {
                $text = get_config('submission.start_update_text_tips');
            } else {
                $text = get_config('submission.start_text_tips');
            }
            Cache::tags($this->cacheTag.'.'.$chatId)->put($cacheKey, $messageCacheData, now()->addDay());
        }
        Cache::tags($this->cacheTag.'.'.$chatId)->put('objectType', $objectType, now()->addDay());

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
        ]);
    }
}
