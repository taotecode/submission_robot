<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
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

    }

    public function feedback(Api $telegram,$botInfo,string $chatId, Collection $chat)
    {
        Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();

        $submissionUser = (new SubmissionUser)->firstOrCreate([
            'bot_id' => $botInfo->id,
            'user_id' => $chatId,
        ], [
            'type' => SubmissionUserType::NORMAL,
            'bot_id'=>$botInfo->id,
            'user_id' => $chatId,
            'user_data' => $chat->toArray(),
            'name' => get_posted_by($chat->toArray()),
        ]);

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
            'text' => get_config('feedback.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START_FEEDBACK),
        ]);
    }

    public function start_complaint(Api $telegram,$botInfo,string $chatId, Collection $chat)
    {
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->put($chatId, $chat->toArray(), now()->addDay());
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('feedback.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::START),
        ]);
    }
}
