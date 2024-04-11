<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;
use App\Models\Bot;
use App\Models\BotUser;
use App\Models\Channel;
use App\Models\Complaint;
use App\Models\Manuscript;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Chat;
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
                    KeyBoardName::SubmitComplaint => $this->start($telegram, $chatId, $chat),
                    KeyBoardName::Restart => $this->restart($telegram, $chatId),
                    KeyBoardName::Cancel => $this->cancel($telegram, $chatId),
                    KeyBoardName::EndSending => $this->end($telegram,$botInfo, $chatId),
                    KeyBoardName::ConfirmComplaint => $this->confirm($telegram,$botInfo, $chatId, $message),
                    default => $this->startUpdateByText($telegram,$botInfo, $chatId, $messageId, $message),
                };
            case 'photo':
            case 'video':
            case 'audio':
                $this->startUpdateByMedia($telegram,$botInfo, $chatId, $messageId, $message, $objectType);
                break;
        }
    }

    /**
     * 进入投诉状态
     * @param Api $telegram
     * @param string $chatId
     * @param $chat
     * @return mixed
     */
    public function start(Api $telegram, string $chatId, $chat): mixed
    {
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->put($chatId, $chat->toArray(), now()->addDay());
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::Cancel),
        ]);
    }

    /**
     * 重新开始
     * @param Api $telegram
     * @param mixed $chatId
     * @return mixed
     */
    public function restart(Api $telegram, mixed $chatId): mixed
    {
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->forget('text');
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->forget('objectType');
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->forget('media_group');
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.restart'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_COMPLAINT),
        ]);
    }

    /**
     * 取消投诉
     * @param Api $telegram
     * @param mixed $chatId
     * @return mixed
     */
    public function cancel(Api $telegram, mixed $chatId): mixed
    {
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->flush();
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.cancel'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START),
        ]);
    }

    /**
     * 更新文本
     * @param Api $telegram
     * @param Bot $botInfo
     * @param mixed $chatId
     * @param mixed $messageId
     * @param $message
     * @return mixed
     */
    public function startUpdateByText(Api $telegram,Bot $botInfo, mixed $chatId, mixed $messageId,$message): mixed
    {
        if (!Cache::tags(CacheKey::Complaint . '.' . $chatId)->has('forward_origin')) {
            return $this->record($telegram,$botInfo, $chatId, $message);
        }

        return $this->updateByText(
            $telegram, $chatId, $messageId, $message,
            CacheKey::Complaint . '.' . $chatId,KeyBoardData::START_COMPLAINT,
            get_config('complaint.start_text_tips'),get_config('complaint.start_update_text_tips')
        );
    }

    /**
     * 更新多媒体
     * @param Api $telegram
     * @param Bot $botInfo
     * @param mixed $chatId
     * @param mixed $messageId
     * @param $message
     * @param $type
     * @return mixed
     */
    public function startUpdateByMedia(Api $telegram,Bot $botInfo, mixed $chatId, mixed $messageId,$message,$type): mixed
    {
        if (!Cache::tags(CacheKey::Complaint . '.' . $chatId)->has('forward_origin')) {
            return $this->record($telegram,$botInfo, $chatId, $message);
        }
        return $this->updateByMedia(
            $telegram, $chatId, $messageId, $message, $type,
            CacheKey::Complaint . '.' . $chatId,KeyBoardData::START_COMPLAINT,
            get_config('complaint.start_text_tips'),get_config('complaint.start_update_text_tips')
        );
    }

    /**
     * 记录来源
     * @param Api $telegram
     * @param Bot $botInfo
     * @param mixed $chatId
     * @param $message
     * @return mixed
     */
    public function record(Api $telegram,Bot $botInfo, mixed $chatId,$message): mixed
    {
        //检查消息是否含有来源
        if (empty($message['forward_origin'])||empty($message->forwardFromChat->username)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }
        //检查来源是否是机器人绑定的频道
        $channel = (new Channel())->where('name',$message->forwardFromChat->username)->first();
        if (empty($channel)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }
        //检查是否为机器人绑定的频道
        if (!in_array($channel->id,$botInfo->channel_ids)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_empty_forward_origin'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }

        //去数据库寻找稿件
        $manuscript=(new Manuscript())->where([
            'bot_id'=>$botInfo->id,
            'channel_id'=>$channel->id,
            'message_id'=>$message['forward_origin']['message_id'],
        ])->first();
        if (!empty($manuscript)) {
            return $this->sendTelegramMessage($telegram, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => get_config('complaint.start_exist_forward_origin'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::Cancel),
            ]);
        }

        Cache::tags(CacheKey::Complaint . '.' . $chatId)->put('forward_origin',[
            'forward_origin'=>$message['forward_origin'],
            'channel_data'=>$channel,
            'manuscript_data'=>$manuscript,
        ], now()->addDay());
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.start_forward_origin'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_COMPLAINT),
        ]);
    }

    /**
     * 结束发送投诉内容
     * @param Api $telegram
     * @param mixed $chatId
     * @param Bot $botInfo
     * @return mixed
     */
    public function end(Api $telegram, $chatId, $botInfo)
    {
        $objectType = Cache::tags(CacheKey::Complaint . '.' . $chatId)->get('objectType');
        $messageId = '';
        $messageCache = [];
        $isEmpty=false;

        $forward_origin_data=Cache::tags(CacheKey::Complaint . '.' . $chatId)->get('forward_origin');
        $channel_data=$forward_origin_data['channel_data'];
        $manuscript_data=$forward_origin_data['manuscript_data'];

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
                'text' => get_config('complaint.is_empty'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(KeyBoardData::START_COMPLAINT),
            ]);
        }

        $custom_header_content = get_config('complaint.custom_header_content');
        $custom_tail_content = get_config('complaint.custom_tail_content');
        $url='https://t.me/'.$channel_data->name.'/'.$manuscript_data->message_id;
        //替换$custom_tail_content中的{url}变量
        $custom_tail_content=str_replace('{url}',$url,$custom_tail_content);

        //发送预览消息
        $this->sendPreviewMessage(
            $telegram, $botInfo, $chatId, $messageCache, $objectType,
            false,false,false,
            $custom_header_content,$custom_tail_content
        );

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('complaint.preview_tips'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::END_COMPLAINT),
        ]);
    }

    public function confirm(Api $telegram, $chatId, $chat, $botInfo)
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

        $botUser=(new BotUser())->where('user_id',$chatId)->first();

        $forward_origin_data=Cache::tags(CacheKey::Complaint . '.' . $chatId)->get('forward_origin');
        $channel_data=$forward_origin_data['channel_data'];
        $manuscript_data=$forward_origin_data['manuscript_data'];

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
            'appendix' => [],
            'approved' => [],
            'reject' => [],
            'one_approved' => [],
            'one_reject' => [],
            'status' => 0,
        ];

        $complaintModel = new Complaint();

        $complaint=$complaintModel->create($sqlData);

        $custom_header_content = get_config('complaint.custom_header_content');
        $custom_tail_content = get_config('complaint.custom_tail_content');
        $url='https://t.me/'.$channel_data->name.'/'.$manuscript_data->message_id;
        //替换$custom_tail_content中的{url}变量
        $custom_tail_content=str_replace('{url}',$url,$custom_tail_content);

        // 发送消息到审核群组
        $this->sendGroupMessage(
            $telegram, $botInfo, $messageCache, $objectType, $complaint->id,
            false,false,false,
            $custom_header_content,$custom_tail_content
        );

        Cache::tags(CacheKey::Complaint . '.' . $chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('complaint.confirm_end'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START),
        ]);
    }
}
