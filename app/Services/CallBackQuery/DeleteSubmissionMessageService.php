<?php

namespace App\Services\CallBackQuery;

use App\Enums\AuditorRole;
use App\Enums\KeyBoardData;
use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Services\SendTelegramMessageService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\User;

class DeleteSubmissionMessageService
{
    use SendTelegramMessageService;
    use AuditorRoleCheckService;
    use UpdateReviewGroupMessageButtonService;

    public function delete_submission_message(Api $telegram,Bot $botInfo,Manuscript $manuscript, ?CallbackQuery $callbackQuery,$chatId,$messageId,User $from): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        //机器人的审核数
        $review_num = $botInfo->review_num;
        //通过人员名单
        $approved = $manuscript->approved;
        //通过人员数量
        $approvedNum = count($approved);
        //拒绝人员名单
        $reject = $manuscript->reject;
        //拒绝人员数量
        $rejectNum = count($reject);

        if ($this->baseCheck($telegram, $callbackQuery->id, $from->id, $reviewGroup->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($telegram, $callbackQuery->id, $from->id, [
                AuditorRole::DELETE_SUBMISSION,
            ]) !== true) {
            return 'ok';
        }

        if ($this->update_review_group_message_button($telegram, $botInfo, $chatId, $messageId, $manuscript, $review_num, $approvedNum, $rejectNum) === true) {
            return 'ok';
        }

        //获取机器人对应的频道ID
        $channelId = '@' . $botInfo->channel->name;

        //删除消息
        try {
            $telegram->deleteMessage([
                'chat_id' => $channelId,
                'message_id' => $manuscript->message_id,
            ]);
            $manuscript->status = ManuscriptStatus::DELETE;
            $manuscript->save();
            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_DELETE),
            ]);
            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => "对应的频道投稿已删除",
                'show_alert' => true,
            ]);
            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
