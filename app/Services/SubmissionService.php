<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;
use App\Enums\SubmissionUserType;
use App\Models\Channel;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class SubmissionService
{
    use SendTelegramMessageService;

    public function index($botInfo, Update $updateData, Api $telegram)
    {
        $chat = $updateData->getChat();
        $chatId = $chat->id;
        $message = $updateData->getMessage();
        $messageId = $message->messageId;
        $objectType = $message->objectType();
        $forwardFrom = $message->forwardFrom ?? '';
        $forwardSignature = $message->forwardSignature ?? '';

        switch ($objectType) {
            case 'text':
                return match ($message->text) {
                    KeyBoardName::CancelSubmission => $this->cancel($telegram, $chatId),
                    KeyBoardName::Restart => $this->start($telegram, $botInfo, $chatId, $chat, get_config('submission.restart')),
                    KeyBoardName::EndSending => $this->end($telegram, $chatId, $botInfo),
                    KeyBoardName::SelectChannel, KeyBoardName::SelectChannelAgain => $this->selectChannel($telegram, $chatId, $botInfo),
                    KeyBoardName::ConfirmSubmissionOpen => $this->confirm($telegram, $chatId, $chat, $botInfo, 0),
                    KeyBoardName::ConfirmSubmissionAnonymous => $this->confirm($telegram, $chatId, $chat, $botInfo, 1),
                    KeyBoardName::Cancel => $this->cancel($telegram, $chatId, $chat, $botInfo, '已取消'),
                    default => $this->startUpdateByText($telegram, $chatId, $messageId, $message),
                };
            case 'photo':
            case 'video':
            case 'audio':
                $this->startUpdateByMedia($telegram, $chatId, $messageId, $message, $objectType);
                break;
        }
    }

    /**
     * 开始API并使用给定的参数。
     *
     * @param Api $telegram API对象。
     * @param string $chatId 聊天ID。
     * @param string $text 要发送的文本消息。默认为"请直接发送您要投稿的内容\r\n发送完毕后，请点击下方的 “结束发送” 按钮。"
     * @return string API调用的结果。可能的值为"ok"或"error"。
     */
    public function start(
        Api        $telegram,
                   $botInfo,
        string     $chatId,
        Collection $chat,
        string     $text = "请直接发送您要投稿的内容\r\n发送完毕后，请点击下方的 “结束发送” 按钮。",
    ): string
    {
        Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();
        //开启投稿服务标识
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put($chatId, $chat->toArray(), now()->addDay());

        $submissionUser = (new SubmissionUser)->firstOrCreate([
            'bot_id' => $botInfo->id,
            'user_id' => $chatId,
        ], [
            'type' => SubmissionUserType::NORMAL,
            'bot_id' => $botInfo->id,
            'user_id' => $chatId,
            'user_data' => $chat->toArray(),
            'name' => get_posted_by($chat->toArray()),
        ]);

        //判断是否是黑名单用户
        if ($submissionUser->type == SubmissionUserType::BLACK) {
            Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();
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
     * 取消投稿。
     *
     * @param Api $telegram Telegram API对象。
     * @param string $chatId 聊天ID。
     * @return string 取消投稿的结果：如果成功则为'ok'，否则为'error'。
     */
    private function cancel(Api $telegram, string $chatId): string
    {
        Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('submission.cancel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START),
        ]);
    }

    private function end(Api $telegram, $chatId, $botInfo): string
    {
        $objectType = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $isEmpty=false;

        //根据不同的类型获取缓存数据,并判断是否为空
        switch ($objectType) {
            case 'text':
                $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('text');
                $messageId = $messageCache['message_id'] ?? '';
                if (!isset($messageCache['text']) || empty($messageCache['text'])) {
                    $isEmpty=true;
                }
                break;
            case 'photo':
                $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('photo');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    !isset($messageCache['photo'][0]['file_id']) || empty($messageCache['photo'][0]['file_id'])
                ) {
                    $isEmpty=true;
                }
                break;
            case 'video':
                $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('video');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    !isset($messageCache['video']['file_id']) || empty($messageCache['video']['file_id'])
                ) {
                    $isEmpty=true;
                }
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group');
                $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group:' . $media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                if (
                    !isset($messageCache[0]['photo'][0]['file_id']) && !isset($messageCache[0]['video']['file_id'])
                ) {
                    $isEmpty=true;
                }
                break;
            case 'audio':
                $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('audio');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    !isset($messageCache['audio']['file_id']) || empty($messageCache['audio']['file_id'])
                ) {
                    $isEmpty=true;
                }
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags(CacheKey::Submission . '.' . $chatId)->has('text')) {
                    $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group');
                    $audioMessageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group:' . $media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                } else {
                    $media_group_id = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group');
                    $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group:' . $media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                }
                break;
            default:
                $isEmpty=true;
                break;
        }

        if ($isEmpty) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.submission_is_empty'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
            ]);
        }

        //发送预览消息
        $this->sendPreviewMessage($telegram, $botInfo, $chatId, $messageCache, $objectType);

        //new 如果bot绑定了多个频道，那么需要提供选择频道的按钮
        if (count($botInfo->channel_ids) > 1) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.preview_tips_channel'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::SELECT_CHANNEL),
            ]);
        }

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('submission.preview_tips'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::END_SUBMISSION),
        ]);
    }

    private function selectChannel(Api $telegram, $chatId, $botInfo): string
    {
        $inline_keyboard = [
            'inline_keyboard' => [
            ],
        ];
        $channels = (new Channel)->whereIn('id', $botInfo->channel_ids)->orderBy('sort_order', 'desc')->get();
        foreach ($channels as $channel) {
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => $channel->appellation, 'callback_data' => 'select_channel:null:' . $channel->id],
            ];
        }

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('submission.select_channel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inline_keyboard),
        ]);
    }

    private function confirm(Api $telegram, $chatId, $chat, $botInfo, $is_anonymous): string
    {
        $objectType = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $messageText = '';

        switch ($objectType) {
            case 'text':
            case 'photo':
            case 'video':
            case 'audio':
                list($messageCache, $messageId, $messageText) = getCacheMessageData($objectType, $chatId, CacheKey::Submission);
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group');
                $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group:' . $media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                foreach ($messageCache as $key => $value) {
                    $messageText .= $value['caption'] ?? '';
                }
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags(CacheKey::Submission . '.' . $chatId)->has('text')) {
                    $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group');
                    $audioMessageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group:' . $media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                    $messageText = $messageCache['text']['text'] ?? '';
                } else {
                    $media_group_id = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group');
                    $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group:' . $media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                    foreach ($messageCache as $key => $value) {
                        $messageText .= $value['caption'] ?? '';
                    }
                }
                break;
        }

        //检查投稿人是否已在数据库中
        $submissionUser = (new SubmissionUser)->firstOrCreate([
            'user_id' => $chat->id,
        ], [
            'type' => 0,
            'user_id' => $chat->id,
            'user_data' => $chat->toArray(),
            'name' => get_posted_by($chat->toArray()),
        ]);

        if (count($botInfo->channel_ids) > 1) {
            $channelId = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('channel_id');
        } else {
            $channelId = $botInfo->channel_ids[0];
        }

        $channel = Channel::find($channelId);

        //将稿件信息存入数据库中
        $sqlData = [
            'bot_id' => $botInfo->id,
            'channel_id' => $channelId,
            'type' => $objectType,
            'text' => $messageText,
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

        $manuscriptModel = new Manuscript();

        $manuscript = $manuscriptModel->create($sqlData);

        //白名单用户直接发布
        if ($submissionUser->type == SubmissionUserType::WHITE) {
            $manuscript->status = 1;
            $channelMessageId = $this->sendChannelMessage($telegram, $botInfo, $manuscript);
            if (!$channelMessageId) {
                return 'ok';
            }
            $manuscript->message_id = $channelMessageId['message_id'] ?? null;
            $manuscript->save();
            Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();

            $chatText = get_config('submission.confirm_white_list');

            if (empty(get_text_title($manuscript->text))) {
                $chatText .= "\r\n\r\n稿件消息直达链接：<a href='https://t.me/" . $channel->name . "/" . $manuscript->message_id . "'>点击查看</a>";
            } else {
                $chatText .= "\r\n\r\n稿件消息直达链接：<a href='https://t.me/" . $channel->name . "/" . $manuscript->message_id . "'>" . get_text_title($manuscript->text) . "</a>";
            }

            $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => $chatText,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::START),
            ]);

            return $this->sendGroupMessageWhiteUser($telegram, $botInfo, $manuscript, $channel);
        }

        $custom_tail_content = "\r\n\r\n 用户投稿至频道：<a href='https://t.me/". $channel->name ."'>" . $channel->appellation. "</a>";

        // 发送消息到审核群组
        $this->sendGroupMessage(
            $telegram, $botInfo, $messageCache, $objectType, $manuscript->id,
            null,null,true,true,true,null,$custom_tail_content
        );
        //            $text=$this->sendGroupMessage($telegram,$botInfo,$messageCache,$objectType,1);

        Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();

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
     * @param Api $telegram API对象。
     * @param string $chatId 聊天ID。
     * @param string $messageId 消息ID。
     * @param Collection $message 要更新的消息。
     * @return string 更新的状态。
     */
    private function startUpdateByText(
        Api        $telegram,
        string     $chatId,
        string     $messageId,
        Collection $message
    ): string
    {
        return $this->updateByText(
            $telegram, $chatId, $messageId, $message,
            CacheKey::Submission . '.' . $chatId, KeyBoardData::START_SUBMISSION,
            get_config('submission.start_text_tips'), get_config('submission.start_update_text_tips')
        );
    }

    private function startUpdateByMedia(Api $telegram, $chatId, $messageId, Collection $message, $type): string
    {
        return $this->updateByMedia(
            $telegram, $chatId, $messageId, $message, $type,
            CacheKey::Submission . '.' . $chatId, KeyBoardData::START_SUBMISSION,
            get_config('submission.start_text_tips'), get_config('submission.start_update_text_tips')
        );
    }
}
