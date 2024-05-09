<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;

class FeedbackService
{
    use SendTelegramMessageService;

    public function feedback(Api $telegram, string $chatId)
    {
        Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('feedback.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::$START_FEEDBACK),
        ]);
    }
}
