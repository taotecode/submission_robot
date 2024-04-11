<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;
use App\Enums\SubmissionUserType;
use App\Models\SubmissionUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class FeedbackService
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
                    KeyBoardName::SubmitComplaint => $this->start_complaint($telegram, $chatId, $chat),
//                    default => $this->updateByText($telegram, $chatId, $messageId, $message),
                };
            case 'photo':
            case 'video':
            case 'audio':
//                $this->media($telegram, $chatId, $messageId, $message, $objectType);
                break;
        }
    }

    public function feedback(Api $telegram, string $chatId)
    {
        Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('feedback.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_FEEDBACK),
        ]);
    }

    /**
     * 进入投诉状态
     * @param Api $telegram
     * @param string $chatId
     * @param Collection $chat
     * @return mixed
     */
    public function start_complaint(Api $telegram, string $chatId, Collection $chat): mixed
    {
        Cache::tags(CacheKey::Complaint . '.' . $chatId)->put($chatId, $chat->toArray(), now()->addDay());
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('feedback.start_complaint'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::Cancel),
        ]);
    }
}
