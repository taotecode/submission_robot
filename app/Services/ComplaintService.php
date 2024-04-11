<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;
use App\Models\Bot;
use Illuminate\Support\Collection;
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
        Log::info('message', $message['forward_origin']);

        switch ($objectType) {
            case 'text':
                return match ($message->text) {
                    KeyBoardName::SubmitComplaint => $this->start_complaint($telegram, $chatId, $chat),
                    default => $this->updateByText($telegram, $chatId, $messageId, $message),
                };
            case 'photo':
            case 'video':
            case 'audio':
//                $this->media($telegram, $chatId, $messageId, $message, $objectType);
                break;
        }
    }

    /**
     * 进入投诉状态
     * @param Api $telegram
     * @param string $chatId
     * @param Collection $chat
     * @return mixed
     */
    public function start(Api $telegram, string $chatId, Collection $chat): mixed
    {
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->put($chatId, $chat->toArray(), now()->addDay());
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('complaint.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::Cancel),
        ]);
    }

    private function updateByText(Api $telegram, mixed $chatId, mixed $messageId, Collection $message)
    {
        if (empty(Cache::tags(CacheKey::Submission . '.' . $chatId)->get('forward_origin'))) {
        }
        return 'ok';
        /*if (empty(Cache::tags(CacheKey::Submission . '.' . $chatId)->get('forward_origin'))) {
            $text = get_config('complaint.start_text_tips');
        } else {
            $text = get_config('complaint.start_update_text_tips');
        }

        if (empty(Cache::tags(CacheKey::Submission . '.' . $chatId)->get('text'))) {
            $text = get_config('complaint.start_text_tips');
        } else {
            $text = get_config('complaint.start_update_text_tips');
        }

        $messageCacheData = $message->toArray();

        if (!empty($messageCacheData['text'])) {
            //消息文字预处理
            $messageCacheData['text'] = htmlspecialchars($messageCacheData['text'], ENT_QUOTES, 'UTF-8');
        }

        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('text', $messageCacheData, now()->addDay());
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('objectType', 'text', now()->addDay());

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_SUBMISSION),
        ]);*/
    }

    public function record(Api $telegram, mixed $chatId,Message $message)
    {
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('forward_origin',[
            'message_id'=>$message->forwardFromMessageId,
            'chat'=>$message->forwardFromChat,
            'from'=>$message->forwardFrom,
        ], now()->addDay());
    }
}
