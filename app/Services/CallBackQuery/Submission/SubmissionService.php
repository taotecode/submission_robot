<?php

namespace App\Services\CallBackQuery\Submission;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\SubmissionUserType;
use App\Models\SubmissionUser;
use App\Services\SendTelegramMessageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;

class SubmissionService
{
    use SendTelegramMessageService;
    public function start(
        Api        $telegram,
                   $botInfo,
        string     $chatId,
        $messageId,
        $callbackQueryId,
        Collection $chat
    )
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
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put($chatId, $chatInfo);
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('forward_origin_type', 0);
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('forward_origin_input_status', 0);
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('forward_origin_input_data', 0);
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('disable_message_preview_status', 3);
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('disable_notification_status', 3);
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put('protect_content_status', 3);

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

        $this->sendTelegramMessage($telegram, 'editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => "您已进入投稿服务中，您可以在消息底部退出投稿服务，接下来你可以直接发送您想要的投稿内容",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text'=>'退出投稿','callback_data'=>'submission_exit'],]
                ]
            ]),
        ]);

        return $telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQueryId,
            'text' => '加载完成',
            'show_alert' => false,
        ]);
    }

    public function exit()
    {

    }
}
