<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
            case 'photo':
            case 'video':
            case 'audio':
                return $this->startUpdateByMedia($telegram, $botInfo, $chatId, $messageId, $message, $objectType);
                break;
            case 'text':
            default:
                return match ($message->text) {
                    get_keyboard_name_config('submission.CancelSubmission', KeyBoardName::CancelSubmission) => $this->cancel($telegram, $botInfo, $chatId),
                    get_keyboard_name_config('submission.Restart', KeyBoardName::Restart) => $this->start($telegram, $botInfo, $chatId, $chat, get_config('submission.restart')),
                    get_keyboard_name_config('submission.EndSending', KeyBoardName::EndSending) => $this->end($telegram, $chatId, $botInfo),
                    get_keyboard_name_config('select_channel.SelectChannel', KeyBoardName::SelectChannel),
                    get_keyboard_name_config('select_channel_end.SelectChannelAgain', KeyBoardName::SelectChannelAgain) => $this->selectChannel($telegram, $chatId, $botInfo),
                    get_keyboard_name_config('submission_end.ConfirmSubmissionOpen', KeyBoardName::ConfirmSubmissionOpen) => $this->confirm($telegram, $chatId, $chat, $botInfo, 0),
                    get_keyboard_name_config('submission_end.ConfirmSubmissionAnonymous', KeyBoardName::ConfirmSubmissionAnonymous), => $this->confirm($telegram, $chatId, $chat, $botInfo, 1),
                    get_keyboard_name_config('common.Cancel', KeyBoardName::Cancel) => $this->cancel($telegram, $botInfo, $chatId),
                    default => $this->startUpdateByText($telegram, $botInfo, $chatId, $messageId, $message),
                };
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
    public function start(
        Api $telegram,
        $botInfo,
        string $chatId,
        Collection $chat,
        string $text = "请直接发送您要投稿的内容\r\n发送完毕后，请点击下方的 “结束发送” 按钮。",
    ): string {
        Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();

        //检查机器人是否开启投稿服务
        if ($botInfo->is_submission == 0) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('submission.not_open'),
                'parse_mode' => 'HTML',
                'reply_markup' => service_isOpen_check_return_keyboard($botInfo),
            ]);
        }

        $chatInfo = $chat->toArray();

        //开启投稿服务标识
        Cache::tags(CacheKey::Submission.'.'.$chatId)->put($chatId, $chatInfo, now()->addDay());

        //存入投稿用户数据
        if (Cache::has(CacheKey::SubmissionUserList.':'.$botInfo->id)) {
            $list = Cache::get(CacheKey::SubmissionUserList.':'.$botInfo->id);
            $list[] = [
                $chatId => now()->addDay()->timestamp,
            ];
        } else {
            $list = [
                $chatId => now()->addDay()->timestamp,
            ];
        }
        Cache::put(CacheKey::SubmissionUserList.':'.$botInfo->id, $list, now()->addWeek());

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
            Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();

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
            'reply_markup' => json_encode(KeyBoardData::$START_SUBMISSION),
        ]);
    }

    /**
     * 取消投稿。
     *
     * @param  Api  $telegram Telegram API对象。
     * @param  string  $chatId 聊天ID。
     * @return string 取消投稿的结果：如果成功则为'ok'，否则为'error'。
     */
    private function cancel(Api $telegram, $botInfo, string $chatId): string
    {
        Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('submission.cancel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
        ]);
    }

    /**
     * 结束发送投稿
     */
    private function end(Api $telegram, $chatId, $botInfo): string
    {
        $cacheTag = CacheKey::Submission.'.'.$chatId;
        $objectType = Cache::tags($cacheTag)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $isEmpty = false;

        // 获取缓存数据并判断是否为空
        switch ($objectType) {
            case 'text':
            case 'photo':
            case 'video':
            case 'audio':
                $messageCache = Cache::tags($cacheTag)->get($objectType);
                $messageId = $messageCache['message_id'] ?? '';
                $isEmpty = isCacheEmpty($objectType, $messageCache);
                break;
            case 'media_group_photo':
            case 'media_group_video':
            case 'media_group_audio':
                $mediaGroupId = Cache::tags($cacheTag)->get('media_group');
                $messageCache = Cache::tags($cacheTag)->get('media_group:'.$mediaGroupId);
                $messageId = $messageCache[0]['message_id'] ?? '';
                if ($objectType === 'media_group_audio' && Cache::tags($cacheTag)->has('text')) {
                    $textCache = Cache::tags($cacheTag)->get('text');
                    $messageId = $textCache['message_id'] ?? '';
                    $messageCache = [
                        'text' => $textCache,
                        'audio' => $messageCache,
                    ];
                }
                $isEmpty = isMediaGroupEmpty($messageCache);
                break;
            default:
                $isEmpty = true;
                break;
        }

        if ($isEmpty) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.submission_is_empty'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::$START_SUBMISSION),
            ]);
        }

        //判断消息是否有来源
        /*if ($botInfo->is_forward_origin === 1 && isset($messageCache['forward_origin'])) {
            $forward_origin = $messageCache['forward_origin'];
            if ($botInfo->is_forward_origin_select === 1) {
                $replyMarkup = KeyBoardData::$FORWARD_ORIGIN_SELECT;
            } else {
                Cache::tags(CacheKey::Submission.'.'.$chatId)->put('forward_origin', $messageCache['forward_origin'], now()->addDay());
            }
        }*/

        //发送预览消息
        $this->sendPreviewMessage($telegram, $botInfo, $chatId, $messageCache, $objectType);

        // 如果 bot 绑定了多个频道，提供选择频道的按钮
        $replyMarkup = count($botInfo->channel_ids) > 1
            ? KeyBoardData::$SELECT_CHANNEL
            : KeyBoardData::$END_SUBMISSION;
        $text = count($botInfo->channel_ids) > 1
            ? get_config('submission.preview_tips_channel')
            : get_config('submission.preview_tips');

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($replyMarkup),
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
                ['text' => $channel->appellation, 'callback_data' => 'select_channel:null:'.$channel->id],
            ];
        }

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('submission.select_channel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inline_keyboard),
        ]);
    }

    /**
     * 确认投稿
     */
    private function confirm(Api $telegram, $chatId, $chat, $botInfo, $is_anonymous): string
    {
        $objectType = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $messageText = '';

        switch ($objectType) {
            case 'text':
            case 'photo':
            case 'video':
            case 'audio':
                [$messageCache, $messageId, $messageText] = getCacheMessageData($objectType, $chatId, CacheKey::Submission);
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('media_group');
                $messageCache = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('media_group:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                foreach ($messageCache as $key => $value) {
                    $messageText .= $value['caption'] ?? '';
                }
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags(CacheKey::Submission.'.'.$chatId)->has('text')) {
                    $messageCache = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('media_group');
                    $audioMessageCache = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                    $messageText = $messageCache['text']['text'] ?? '';
                } else {
                    $media_group_id = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('media_group');
                    $messageCache = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('media_group:'.$media_group_id);
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
            $channelId = Cache::tags(CacheKey::Submission.'.'.$chatId)->get('channel_id');
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
            if (! $channelMessageId) {
                return 'ok';
            }
            $manuscript->message_id = $channelMessageId['message_id'] ?? null;
            $manuscript->save();
            Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();

            $chatText = get_config('submission.confirm_white_list');

            if (empty(get_text_title($manuscript->text))) {
                $chatText = str($chatText)->swap([
                    '{url}' => 'https://t.me/'.$channel->name.'/'.$manuscript->message_id,
                    '{title}' => '点击查看',
                ]);
            } else {
                $chatText = str($chatText)->swap([
                    '{url}' => 'https://t.me/'.$channel->name.'/'.$manuscript->message_id,
                    '{title}' => get_text_title($manuscript->text),
                ]);
            }

            $chatText = html_entity_decode($chatText, ENT_QUOTES, 'UTF-8');

            $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => $chatText,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
            ]);

            return $this->sendGroupMessageWhiteUser($telegram, $botInfo, $manuscript, $channel);
        }

        $custom_tail_content = "\r\n\r\n 用户投稿至频道：<a href='https://t.me/".$channel->name."'>".$channel->appellation.'</a>';

        // 发送消息到审核群组
        $this->sendGroupMessage(
            $telegram, $botInfo, $messageCache, $objectType, $manuscript->id,
            null, null, true, true, true, null, $custom_tail_content
        );
        //            $text=$this->sendGroupMessage($telegram,$botInfo,$messageCache,$objectType,1);

        Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('submission.confirm'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
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
    public function startUpdateByText(
        Api $telegram,
        Bot $botInfo,
        string $chatId,
        string $messageId,
        Collection $message
    ): string {
        return $this->updateByText(
            $telegram, $botInfo, $chatId, $messageId, $message,
            CacheKey::Submission.'.'.$chatId, KeyBoardData::$START_SUBMISSION,
            get_config('submission.start_text_tips'), get_config('submission.start_update_text_tips')
        );
    }

    public function startUpdateByMedia(Api $telegram, $botInfo, $chatId, $messageId, Collection $message, $type): string
    {
        return $this->updateByMedia(
            $telegram, $botInfo, $chatId, $messageId, $message, $type,
            CacheKey::Submission.'.'.$chatId, KeyBoardData::$START_SUBMISSION,
            get_config('submission.start_text_tips'), get_config('submission.start_update_text_tips')
        );
    }
}
