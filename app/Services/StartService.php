<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\InlineKeyBoardData;
use App\Enums\KeyBoardName;
use App\Models\Bot;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class StartService
{
    use SendTelegramMessageService;

    public function index(Bot $botInfo, Update $updateData, Api $telegram)
    {
        $chat = $updateData->getChat();
        $chatId = $chat->id;
        $message = $updateData->getMessage();
        $messageId = $message->messageId;
        $objectType = $message->objectType();

        $submissionService = new SubmissionService();
        $feedbackService = new FeedbackService();
        $complaintService = new ComplaintService();

        //如果用户已经进入投稿状态，直接进入投稿服务
        if (Cache::tags(CacheKey::Submission.'.'.$chatId)->has($chatId)) {
            return $submissionService->index($botInfo, $updateData, $telegram);
        }
        //如果用户已经进入投诉状态，直接进入投诉服务
        if (Cache::tags(CacheKey::Complaint.'.'.$chatId)->has($chatId)) {
            return $complaintService->index($botInfo, $updateData, $telegram);
        }
        //如果用户已经进入建议状态，直接进入建议服务
        if (Cache::tags(CacheKey::Suggestion.'.'.$chatId)->has($chatId)) {
            return $this->index($botInfo, $updateData, $telegram);
        }

        return match ($objectType) {
            'text' => match ($message->text) {
                get_keyboard_name_config('start.StartSubmission', KeyBoardName::StartSubmission) => $submissionService->start($telegram, $botInfo, $chatId, $chat, get_config('submission.start')),
                get_keyboard_name_config('start.Feedback', KeyBoardName::Feedback) => $feedbackService->feedback($telegram, $chatId),
                get_keyboard_name_config('feedback.SubmitComplaint', KeyBoardName::SubmitComplaint) => $complaintService->start($telegram, $botInfo, $chatId, $chat),
                get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter) => $this->help($telegram, $botInfo, $chatId),
                get_keyboard_name_config('common.Cancel', KeyBoardName::Cancel) => $this->start($telegram, $botInfo, $chatId),
                default => $this->error_for_text($telegram, $chatId, $messageId),
            },
            default => $this->error_for_text($telegram, $chatId, $messageId),
        };
    }

    public function start(Api $telegram, $botInfo, string $chatId)
    {
        Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->flush();

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('submission.error_for_text'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
        ]);
    }

    /**
     * 发送帮助中心消息。
     */
    public function help(Api $telegram, $botInfo, string $chatId): mixed
    {
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => get_config('help.start'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
        ]);
    }

    /**
     * 向Telegram聊天发送文本的错误消息。
     *
     * @param  Api  $telegram Telegram API实例。
     * @param  string  $chatId 要发送消息的聊天ID。
     * @param  string  $messageId 要回复的消息ID。
     * @return string 操作的结果（'ok'或'error'）。
     */
    private function error_for_text(Api $telegram, string $chatId, string $messageId): string
    {
        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'reply_to_message_id' => $messageId,
            'text' => get_config('submission.error_for_text'),
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(InlineKeyBoardData::$ERROR_FOR_MESSAGE),
        ]);
    }
}
