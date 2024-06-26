<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\InlineKeyBoardData;
use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;
use App\Models\Bot;
use App\Models\BotUser;
use App\Models\Channel;
use App\Models\Complaint;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

class ComplaintService
{
    use SendTelegramMessageService;

    public function index(Bot $botInfo, Update $updateData, Api $telegram)
    {
        $chat = $updateData->getChat();
        $chatId = $chat->id;
        $message = $updateData->getMessage();
        $messageId = $message->messageId;
        $objectType = $message->objectType();
        $forwardFrom = $message->forwardFrom ?? '';
        $forwardSignature = $message->forwardSignature ?? '';
        //        Log::info('message', $message['forward_origin']);

        switch ($objectType) {
            case 'text':
                return match ($message->text) {
                    get_keyboard_name_config('feedback.SubmitComplaint', KeyBoardName::SubmitComplaint) => $this->start($telegram, $botInfo, $chatId, $chat),
                    get_keyboard_name_config('complaint.Restart', KeyBoardName::Restart) => $this->restart($telegram, $chatId),
                    get_keyboard_name_config('complaint.Cancel', KeyBoardName::Cancel) => $this->cancel($telegram,$botInfo, $chatId),
                    get_keyboard_name_config('complaint.EndSending', KeyBoardName::EndSending) => $this->end($telegram, $botInfo, $chatId),
                    get_keyboard_name_config('complaint_end.ConfirmComplaint', KeyBoardName::ConfirmComplaint) => $this->confirm($telegram, $botInfo, $chatId, $chat),
                    default => $this->startUpdateByText($telegram, $botInfo, $chatId, $messageId, $message),
                };
            case 'photo':
            case 'video':
            case 'audio':
                $this->startUpdateByMedia($telegram, $botInfo, $chatId, $messageId, $message, $objectType);
                break;
        }
    }

    /**
     * 进入投诉状态
     */
    public function start(Api $telegram, $botInfo, string $chatId, $chat): mixed
    {
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->flush();

        //检查机器人是否开启投稿服务
        if ($botInfo->is_complaint == 0) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.not_open'),
                'parse_mode' => 'HTML',
                'reply_markup' => service_isOpen_check_return_keyboard($botInfo),
            ]);
        }

        Cache::tags(CacheKey::Complaint.'.'.$chatId)->put($chatId, $chat->toArray(), now()->addDay());

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::Cancel),
        ]);
    }

    /**
     * 重新开始
     */
    public function restart(Api $telegram, mixed $chatId): mixed
    {
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->forget('text');
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->forget('objectType');
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->forget('media_group');

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.restart'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::$START_COMPLAINT),
        ]);
    }

    /**
     * 取消投诉
     */
    public function cancel(Api $telegram,$botInfo, mixed $chatId): mixed
    {
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.cancel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
        ]);
    }

    /**
     * 更新文本
     */
    public function startUpdateByText(Api $telegram, Bot $botInfo, mixed $chatId, mixed $messageId, $message): mixed
    {
        if (! Cache::tags(CacheKey::Complaint.'.'.$chatId)->has('forward_origin')) {
            return $this->record($telegram, $botInfo, $chatId, $message);
        }

        return $this->updateByText(
            $telegram, $botInfo, $chatId, $messageId, $message,
            CacheKey::Complaint.'.'.$chatId, KeyBoardData::$START_COMPLAINT,
            get_config('complaint.start_text_tips'), get_config('complaint.start_update_text_tips')
        );
    }

    /**
     * 更新多媒体
     */
    public function startUpdateByMedia(Api $telegram, Bot $botInfo, mixed $chatId, mixed $messageId, $message, $type): mixed
    {
        if (! Cache::tags(CacheKey::Complaint.'.'.$chatId)->has('forward_origin')) {
            return $this->record($telegram, $botInfo, $chatId, $message);
        }

        return $this->updateByMedia(
            $telegram, $botInfo, $chatId, $messageId, $message, $type,
            CacheKey::Complaint.'.'.$chatId, KeyBoardData::$START_COMPLAINT,
            get_config('complaint.start_text_tips'), get_config('complaint.start_update_text_tips')
        );
    }

    /**
     * 记录来源
     */
    public function record(Api $telegram, Bot $botInfo, mixed $chatId, $message): mixed
    {
        //检查消息是否含有来源
        if (empty($message['forward_origin']) || empty($message->forwardFromChat->username)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin')."\r\n没有消息来源",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }
        //检查来源是否是机器人绑定的频道
        $channel = (new Channel())->where('name', $message->forwardFromChat->username)->first();
        if (empty($channel)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin')."\r\n没有绑定的频道",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }
        //检查是否为机器人绑定的频道
        if (! in_array($channel->id, $botInfo->channel_ids)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin')."\r\n频道绑定错误",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }

        //去数据库寻找稿件
        $manuscript = (new Manuscript())->where([
            'bot_id' => $botInfo->id,
            'channel_id' => $channel->id,
            'message_id' => $message->forwardFromMessageId,
        ])->first();
        if (empty($manuscript)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin')."\r\n找不到对应稿件",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }

        Cache::tags(CacheKey::Complaint.'.'.$chatId)->put('forward_origin', [
            'forward_origin' => $message['forward_origin'],
            'channel_data' => $channel,
            'manuscript_data' => $manuscript,
        ], now()->addDay());

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.start_forward_origin'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::$START_COMPLAINT),
        ]);
    }

    /**
     * 结束发送投诉内容
     *
     * @param Api $telegram
     * @param Bot $botInfo
     * @param mixed $chatId
     * @return mixed
     */
    public function end(Api $telegram, $botInfo, $chatId): mixed
    {
        $objectType = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $isEmpty = false;

        $forward_origin_data = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('forward_origin');
        if (empty($forward_origin_data)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }
        $channel_data = $forward_origin_data['channel_data'];
        $manuscript_data = $forward_origin_data['manuscript_data'];

        //根据不同的类型获取缓存数据,并判断是否为空
        switch ($objectType) {
            case 'text':
                $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('text');
                $messageId = $messageCache['message_id'] ?? '';
                if (! isset($messageCache['text']) || empty($messageCache['text'])) {
                    $isEmpty = true;
                }
                break;
            case 'photo':
                $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('photo');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    ! isset($messageCache['photo'][0]['file_id']) || empty($messageCache['photo'][0]['file_id'])
                ) {
                    $isEmpty = true;
                }
                break;
            case 'video':
                $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('video');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    ! isset($messageCache['video']['file_id']) || empty($messageCache['video']['file_id'])
                ) {
                    $isEmpty = true;
                }
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group');
                $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                if (
                    ! isset($messageCache[0]['photo'][0]['file_id']) && ! isset($messageCache[0]['video']['file_id'])
                ) {
                    $isEmpty = true;
                }
                break;
            case 'audio':
                $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('audio');
                $messageId = $messageCache['message_id'] ?? '';
                if (
                    ! isset($messageCache['audio']['file_id']) || empty($messageCache['audio']['file_id'])
                ) {
                    $isEmpty = true;
                }
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags(CacheKey::Complaint.'.'.$chatId)->has('text')) {
                    $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group');
                    $audioMessageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                } else {
                    $media_group_id = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group');
                    $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                }
                break;
            default:
                $isEmpty = true;
                break;
        }

        if ($isEmpty) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'reply_to_message_id' => $messageId,
                'text' => get_config('complaint.is_empty'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::$START_COMPLAINT),
            ]);
        }

        $custom_header_content = get_config('complaint.custom_header_content')."\n\n";
        $custom_tail_content = "\n\n".get_config('complaint.custom_tail_content');
        $url = 'https://t.me/'.$channel_data->name.'/'.$manuscript_data->message_id ?? '';
        //替换$custom_tail_content中的{url}变量
        $custom_tail_content = str_replace('{url}', $url, $custom_tail_content);

        //发送预览消息
        $this->sendPreviewMessage(
            $telegram, $botInfo, $chatId, $messageCache, $objectType,
            false, false, false,
            $custom_header_content, $custom_tail_content
        );

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('complaint.preview_tips'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::$END_COMPLAINT),
        ]);
    }

    public function confirm(Api $telegram, $botInfo, $chatId, $chat)
    {
        $objectType = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $messageText = '';

        switch ($objectType) {
            case 'text':
            case 'photo':
            case 'video':
            case 'audio':
                [$messageCache, $messageId, $messageText] = getCacheMessageData($objectType, $chatId, CacheKey::Complaint);
                break;
            case 'media_group_photo':
            case 'media_group_video':
                $media_group_id = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group');
                $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group:'.$media_group_id);
                $messageId = $messageCache[0]['message_id'] ?? '';
                foreach ($messageCache as $key => $value) {
                    $messageText .= $value['caption'] ?? '';
                }
                break;
            case 'media_group_audio':
                //特殊情况，需要先判断有没有文字，如果有，那就是文字+多音频
                if (Cache::tags(CacheKey::Complaint.'.'.$chatId)->has('text')) {
                    $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('text');
                    $messageId = $messageCache['message_id'] ?? '';
                    $media_group_id = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group');
                    $audioMessageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageCache = [
                        'text' => $messageCache,
                        'audio' => $audioMessageCache,
                    ];
                    $messageText = $messageCache['text']['text'] ?? '';
                } else {
                    $media_group_id = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group');
                    $messageCache = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('media_group:'.$media_group_id);
                    $messageId = $messageCache[0]['message_id'] ?? '';
                    foreach ($messageCache as $key => $value) {
                        $messageText .= $value['caption'] ?? '';
                    }
                }
                break;
        }

        $botUser = (new BotUser())->where('user_id', $chatId)->first();

        $forward_origin_data = Cache::tags(CacheKey::Complaint.'.'.$chatId)->get('forward_origin');
        if (empty($forward_origin_data)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }
        $channel_data = $forward_origin_data['channel_data'];
        $manuscript_data = $forward_origin_data['manuscript_data'];

        //将投诉信息存入数据库中
        $sqlData = [
            'bot_id' => $botInfo->id,
            'channel_id' => $channel_data->id,
            'message_id' => $manuscript_data->message_id,
            'type' => $objectType,
            'text' => $messageText,
            'posted_by' => $chat->toArray(),
            'posted_by_id' => $botUser->id,
            'data' => $messageCache,
            'approved' => [],
            'reject' => [],
            'one_approved' => [],
            'one_reject' => [],
            'status' => 0,
        ];

        $complaintModel = new Complaint();

        $complaint = $complaintModel->create($sqlData);

        $custom_header_content = get_config('complaint.custom_header_content')."\n\n";
        $custom_tail_content = "\n\n".get_config('complaint.custom_tail_content');
        $url = 'https://t.me/'.$channel_data->name.'/'.$manuscript_data->message_id ?? '';
        //替换$custom_tail_content中的{url}变量
        $custom_tail_content = str_replace('{url}', $url, $custom_tail_content);

        // 发送消息到审核群组
        $this->sendGroupMessage(
            $telegram, $botInfo, $messageCache, $objectType, $complaint->id,
            null, InlineKeyBoardData::REVIEW_GROUP_COMPLAINT, false, false, false,
            $custom_header_content, $custom_tail_content
        );

        Cache::tags(CacheKey::Complaint.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('complaint.confirm_end'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
        ]);
    }
}
