<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\InlineKeyBoardData;
use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\BotUser;
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

    public function index($botInfo,BotUser $botUser, Update $updateData, Api $telegram)
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
                    get_keyboard_name_config('submission.EndSending', KeyBoardName::EndSending) => $this->end($telegram,$botInfo,$botUser,$chatId,$chat),
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
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put($chatId, $chatInfo, now()->addDay());
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('forward_origin_type', 0, now()->addDay());
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('forward_origin_input_status', 0, now()->addDay());
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('forward_origin_input_data', 0, now()->addDay());
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('disable_message_preview_status', 3, now()->addDay());
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('disable_notification_status', 3, now()->addDay());
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('protect_content_status', 3, now()->addDay());

        //存入投稿用户数据
        if (Cache::has(CacheKey::SubmissionUserList . ':' . $botInfo->id)) {
            $list = Cache::get(CacheKey::SubmissionUserList . ':' . $botInfo->id);
            $list[] = [
                $chatId => now()->addDay()->timestamp,
            ];
        } else {
            $list = [
                $chatId => now()->addDay()->timestamp,
            ];
        }
        Cache::put(CacheKey::SubmissionUserList . ':' . $botInfo->id, $list, now()->addWeek());

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
            'reply_markup' => json_encode(KeyBoardData::$START_SUBMISSION),
        ]);
    }

    /**
     * 取消投稿。
     *
     * @param Api $telegram Telegram API对象。
     * @param string $chatId 聊天ID。
     * @return string 取消投稿的结果：如果成功则为'ok'，否则为'error'。
     */
    private function cancel(Api $telegram, $botInfo, string $chatId): string
    {
        Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();

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
    private function end(Api $telegram, $botInfo,$botUser,$chatId,$chat): string
    {
        $cacheTag = CacheKey::Submission . '.' . $chatId;
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
                $messageCache = Cache::tags($cacheTag)->get('media_group:' . $mediaGroupId);
                $messageId = $messageCache['media_group'][0]['message_id'] ?? '';
                if ($objectType === 'media_group_audio' && Cache::tags($cacheTag)->has('text')) {
                    $textCache = Cache::tags($cacheTag)->get('text');
                    $messageId = $textCache['message_id'] ?? '';
                    $messageCache = [
                        'text' => $textCache,
                        'audio' => $messageCache,
                    ];
                }
                $isEmpty = isMediaGroupEmpty($messageCache['media_group']);
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
        if ($botInfo->is_forward_origin == 1) {//机器人启动了【消息来源自动标注】
            //is_forward_origin_select=1表示用户可以选择来源，0表示机器人自动标注
            if (isset($messageCache['forward_origin'])) {//如果消息中存在来源
                if ($botInfo->is_forward_origin_select == 1) {//用户可以选择是否引用来源
                    if (Cache::tags($cacheTag)->get('forward_origin_type') == 0) {
                        return $this->sendTelegramMessage($telegram, 'sendMessage', [
                            'chat_id' => $chatId,
                            'reply_to_message_id' => $messageId,
                            'text' => get_config('submission.select_forward_origin_is_tip'),
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode(InlineKeyBoardData::$FORWARD_ORIGIN_SELECT),
                        ]);
                    } else {
                        $messageCache['forward_origin_type'] = Cache::tags($cacheTag)->get('forward_origin_type');
                    }
                } else {//用户不能自主选择是否引用来源，并直接强制引用来源。
                    $messageCache['forward_origin_type'] = 1;
                }
            } elseif ($botInfo->is_forward_origin_input == 1) {//没有来源时，选择让用户主动输入
                if (Cache::tags($cacheTag)->get('forward_origin_input_status') == 0 || Cache::tags($cacheTag)->get('forward_origin_input_status') == 01) {
                    //用户已输入
                    if (!empty(Cache::tags($cacheTag)->get('forward_origin_input_data'))) {
                        Cache::tags($cacheTag)->put('forward_origin_input_status', 1);//标记状态为已输入
                        $messageCache['forward_origin_input_status'] = 1;
                        $messageCache['forward_origin_input_data'] = Cache::tags($cacheTag)->get('forward_origin_input_data');
                    } else {
                        //用户未输入
                        $telegramReturnMessage = $this->sendTelegramMessage($telegram, 'sendMessage', [
                            'chat_id' => $chatId,
                            'reply_to_message_id' => $messageId,
                            'text' => get_config('submission.select_forward_origin_input_tip'),
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode([
                                'force_reply' => true,
                                'selective' => true,
                            ]),
                        ], true);
                        $telegramReturnMessageId = $telegramReturnMessage->message_id;
                        Cache::tags($cacheTag)->put('forward_origin_input_id', $telegramReturnMessageId);//标记需要回复的消息ID
                        Cache::tags($cacheTag)->put('forward_origin_input_status', 01);//标记状态为待输入
                        return $this->sendTelegramMessage($telegram, 'sendMessage', [
                            'chat_id' => $chatId,
                            'reply_to_message_id' => $telegramReturnMessageId,
                            'text' => get_config('submission.select_forward_origin_input_c_tip'),
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode(InlineKeyBoardData::$FORWARD_ORIGIN_INPUT),
                        ]);
                    }
                } elseif (Cache::tags($cacheTag)->get('forward_origin_input_status') == 1) {
                    $messageCache['forward_origin_input_status'] = 1;
                    $messageCache['forward_origin_input_data'] = Cache::tags($cacheTag)->get('forward_origin_input_data');
                }
            }
        }

        //消息预览功能
        $disable_message_preview=$this->selectCommonByYesOrNo(
            $botInfo,$botUser,
            $messageCache, $botInfo->is_link_preview,$cacheTag,
            'disable_message_preview_status','disable_message_preview','is_link_preview'
        );
        if (!$disable_message_preview){
            $disable_message_preview_message=$this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.disable_message_preview_select_tip'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(InlineKeyBoardData::$DISABLE_MESSAGE_PREVIEW),
            ],true);
            if (isset($disable_message_preview_message->message_id)){
                Cache::tags($cacheTag)->put('disable_message_preview_id', $disable_message_preview_message->message_id);//标记需要回复的消息ID
                return 'ok';
            }else{
                return 'error';
            }
        }else{
            $messageCache=$disable_message_preview;
        }

        //消息静默发送
        $disable_notification=$this->selectCommonByYesOrNo(
            $botInfo,$botUser,
            $messageCache, $botInfo->is_disable_notification,$cacheTag,
            'disable_notification_status','disable_notification','is_disable_notification'
        );
        if (!$disable_notification){
            $disable_notification_message=$this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.disable_notification_select_tip'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(InlineKeyBoardData::$DISABLE_NOTIFICATION),
            ],true);
            if (isset($disable_notification_message->message_id)){
                Cache::tags($cacheTag)->put('disable_notification_id', $disable_notification_message->message_id);//标记需要回复的消息ID
                return 'ok';
            }else{
                return 'error';
            }
        }else{
            $messageCache=$disable_notification;
        }

        //消息禁止被转发和保存
        $protect_content=$this->selectCommonByYesOrNo(
            $botInfo,$botUser,
            $messageCache, $botInfo->is_protect_content,$cacheTag,
            'protect_content_status','protect_content','is_protect_content'
        );
        if (!$protect_content){
            $protect_content_message=$this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.protect_content_select_tip'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(InlineKeyBoardData::$PROTECT_CONTENT),
            ],true);
            if (isset($protect_content_message->message_id)){
                Cache::tags($cacheTag)->put('protect_content_id', $protect_content_message->message_id);//标记需要回复的消息ID
                return 'ok';
            }else{
                return 'error';
            }
        }else{
            $messageCache=$protect_content;
        }

        //更新稿件缓存
        switch ($objectType) {
            case 'text':
            case 'photo':
            case 'video':
            case 'audio':
                Cache::tags($cacheTag)->put($objectType, $messageCache, now()->addDay());
                break;
            case 'media_group_photo':
            case 'media_group_video':
            case 'media_group_audio':
                $mediaGroupId = Cache::tags($cacheTag)->get('media_group');
                Cache::tags($cacheTag)->put('media_group:' . $mediaGroupId, $messageCache, now()->addDay());
                break;
        }

        //发送预览消息
        $this->sendPreviewMessage($telegram, $botInfo, $chatId, $messageCache, $objectType);

        // 如果 bot 绑定了多个频道，提供选择频道的按钮
        $replyMarkup = count($botInfo->channel_ids) > 1
            ? KeyBoardData::$SELECT_CHANNEL
            : KeyBoardData::$END_SUBMISSION;
        $text = count($botInfo->channel_ids) > 1
            ? get_config('submission.preview_tips_channel')
            : get_config('submission.preview_tips');

        //
        if ($botInfo->is_user_setting==1 && count($botInfo->channel_ids) <=1){
            if ($botUser['is_anonymous']==1){//匿名
                return $this->confirm($telegram,$chatId,$chat,$botInfo,1);
            }else{
                return $this->confirm($telegram,$chatId,$chat,$botInfo,1);
            }
        }

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
                ['text' => $channel->appellation, 'callback_data' => 's_p_m_s_channel:null:' . $channel->id],
            ];
        }

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('submission.select_channel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inline_keyboard),
        ]);
    }

    private function selectCommonByYesOrNo($botInfo,$botUser,$messageCache,$functionData,$cacheTag,$cacheKey,$messageKey,$botUserSettingKey='')
    {
        if ($functionData==0){//关闭消息预览
            $messageCache[$messageKey] = 0;
        }elseif ($functionData==1){//开启消息预览
            $messageCache[$messageKey] = 1;
        }elseif ($functionData==2){//用户自主选择
            if ($botInfo->is_user_setting==1){
                if ($botUser[$botUserSettingKey]==1){
                    $messageCache[$messageKey] = 1;
                }else{
                    $messageCache[$messageKey] = 0;
                }
            }else{
                $status=Cache::tags($cacheTag)->get($cacheKey);
                if ($status==3){
                    return false;
                }else{
                    $messageCache[$messageKey] = $status;
                }
            }
        }
        return $messageCache;
    }

    /**
     * 确认投稿
     */
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
                [$messageCache, $messageId, $messageText] = getCacheMessageData($objectType, $chatId, CacheKey::Submission);
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group');
                $messageCache = Cache::tags(CacheKey::Submission . '.' . $chatId)->get('media_group:' . $media_group_id);
                $messageId = $messageCache['media_group'][0]['message_id'] ?? '';
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
            if (!isset($channelMessageId['message_id'])){
                $channelMessageId=$channelMessageId[0];
            }
            $manuscript->message_id = $channelMessageId['message_id'] ?? null;
            $manuscript->save();
            Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();

            $chatText = get_config('submission.confirm_white_list');

            if (empty(get_text_title($manuscript->text))) {
                $chatText = str($chatText)->swap([
                    '{url}' => 'https://t.me/' . $channel->name . '/' . $manuscript->message_id,
                    '{title}' => '点击查看',
                ]);
            } else {
                $chatText = str($chatText)->swap([
                    '{url}' => 'https://t.me/' . $channel->name . '/' . $manuscript->message_id,
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

        $custom_tail_content = "\n\n 用户投稿至频道：<a href='https://t.me/" . $channel->name . "'>" . $channel->appellation . '</a>';
        //添加相关配置信息
        $custom_tail_content.= "\n\n 稿件配置：";
        $custom_tail_content.= "\n ·是否匿名：" . (($is_anonymous==1)?"是":"否 【".get_posted_by($chat)."】");
        $custom_tail_content.= "\n ·消息是否禁止被转发和保存：" . (($messageCache['protect_content']==1)?"是":"否");
        $custom_tail_content.= "\n ·消息是否静默方式发送：" . (($messageCache['disable_notification']==1)?"是":"否");
        $custom_tail_content.= "\n ·消息是否允许链接预览：" . (($messageCache['disable_message_preview']==1)?"是":"否");

        // 发送消息到审核群组
        $this->sendGroupMessage(
            $telegram, $botInfo, $messageCache, $objectType, $manuscript->id,
            null, null, true, true, true, null, $custom_tail_content
        );
        //            $text=$this->sendGroupMessage($telegram,$botInfo,$messageCache,$objectType,1);

        Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();

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
     * @param Api $telegram API对象。
     * @param string $chatId 聊天ID。
     * @param string $messageId 消息ID。
     * @param Collection $message 要更新的消息。
     * @return string 更新的状态。
     */
    public function startUpdateByText(
        Api        $telegram,
        Bot        $botInfo,
        string     $chatId,
        string     $messageId,
        Collection $message
    ): string
    {
        $cacheTag = CacheKey::Submission . '.' . $chatId;
        if (Cache::tags($cacheTag)->get('forward_origin_input_status') == 01) {

            //获取$message中的回复消息ID
            $reply_to_message_id = $message->replyToMessage->messageId ?? null;
            $forward_origin_input_id = Cache::tags($cacheTag)->get('forward_origin_input_id');
            if (empty($reply_to_message_id) || $reply_to_message_id != $forward_origin_input_id) {
                return $this->sendTelegramMessage($telegram, 'sendMessage', [
                    'chat_id' => $chatId,
                    'reply_to_message_id' => $forward_origin_input_id,
                    'text' => get_config('submission.select_forward_origin_input_id_error_tip'),
                    'parse_mode' => 'HTML',
                ]);
            }

            $messageCacheData = preprocessMessageText($message, $botInfo);
            $messageText = $messageCacheData['text'];
            Cache::tags($cacheTag)->put('forward_origin_input_data', $messageText, now()->addDay());

            $text = str(get_config('submission.select_forward_origin_input_confirm_tip'))->swap([
                '{data}' => $messageText,
            ]);

            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $forward_origin_input_id,
                'text' => $text->toString(),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::$START_SUBMISSION),
            ]);
        }
        return $this->updateByText(
            $telegram, $botInfo, $chatId, $messageId, $message,
            CacheKey::Submission . '.' . $chatId, KeyBoardData::$START_SUBMISSION,
            get_config('submission.start_text_tips'), get_config('submission.start_update_text_tips')
        );
    }

    public function startUpdateByMedia(Api $telegram, $botInfo, $chatId, $messageId, Collection $message, $type): string
    {
        $cacheTag = CacheKey::Submission . '.' . $chatId;
        if (Cache::tags($cacheTag)->get('forward_origin_input_status') == 01) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('submission.select_forward_origin_input_media_tip'),
                'parse_mode' => 'HTML',
            ]);
        }
        return $this->updateByMedia(
            $telegram, $botInfo, $chatId, $messageId, $message, $type,
            CacheKey::Submission . '.' . $chatId, KeyBoardData::$START_SUBMISSION,
            get_config('submission.start_text_tips'), get_config('submission.start_update_text_tips')
        );
    }
}
