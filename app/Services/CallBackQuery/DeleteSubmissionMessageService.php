<?php

namespace App\Services\CallBackQuery;

use App\Enums\AuditorRole;
use App\Enums\InlineKeyBoardData;
use App\Enums\KeyBoardData;
use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Services\SendPostedByMessageService;
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
    use SendPostedByMessageService;

    public function delete_submission_message(Api $telegram,Bot $botInfo,?Manuscript $manuscript, ?CallbackQuery $callbackQuery,$chatId,$messageId,User $from): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        //机器人的审核数
        $review_approved_num = $botInfo->review_approved_num;
        $review_reject_num = $botInfo->review_reject_num;
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

        if ($this->update_review_group_message_button($telegram, $botInfo, $chatId, $messageId, $manuscript, $review_approved_num,$review_reject_num, $approvedNum, $rejectNum,true) === true) {
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

            $inline_keyboard = InlineKeyBoardData::$REVIEW_GROUP_DELETE;
            $inline_keyboard['inline_keyboard'][0][0]['callback_data'] .= ":".$manuscript->id;

            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode($inline_keyboard),
            ]);
            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => "对应的频道投稿已删除",
                'show_alert' => true,
            ]);
            $this->sendPostedByMessage($telegram, $manuscript,$botInfo, ManuscriptStatus::DELETE);
            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);
            return 'error';
        }
    }
}
