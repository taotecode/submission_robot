<?php

namespace App\Services\CallBackQuery;

use App\Enums\KeyBoardData;
use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Services\SendTelegramMessageService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait UpdateReviewGroupMessageButtonService
{

    use SendTelegramMessageService;

    public function update_review_group_message_button(Api $telegram,Bot $botInfo,$chatId,$messageId,Manuscript $manuscript,$review_num,$approvedNum,$rejectNum,$isDelete=false)
    {
        $inline_keyboard_approved=KeyBoardData::REVIEW_GROUP_APPROVED;
        $inline_keyboard_approved['inline_keyboard'][0][0]['callback_data'] .= ":".$manuscript->id;
        $inline_keyboard_approved['inline_keyboard'][0][1]['url'] .= $botInfo->channel->name."/".$manuscript->message_id;
        $inline_keyboard_approved['inline_keyboard'][1][0]['callback_data'] .= ':'.$manuscript->id;

        $inline_keyboard_reject=KeyBoardData::REVIEW_GROUP_REJECT;
        $inline_keyboard_reject['inline_keyboard'][0][0]['callback_data'] .= ":".$manuscript->id;

        $inline_keyboard_delete = KeyBoardData::REVIEW_GROUP_DELETE;
        $inline_keyboard_delete['inline_keyboard'][0][0]['callback_data'] .= ":".$manuscript->id;

        $text=$manuscript->text;

        $text .= "\r\n ------------------- \r\n";

        $text .= "\r\n审核通过人员：";

        foreach ($manuscript->approved as $approved){
            $text .= "\r\n".get_posted_by($approved);
        }

        if (!empty($manuscript->one_approved)){
            $text .= "\r\n".get_posted_by($manuscript->one_approved);
        }

        $text .= "\r\n审核拒绝人员：";

        foreach ($manuscript->reject as $reject){
            $text .= "\r\n".get_posted_by($reject);
        }

        if (!empty($manuscript->one_reject)){
            $text .= "\r\n".get_posted_by($manuscript->one_reject);
        }

        $text .= "\r\n审核通过时间：".date('Y-m-d H:i:s',time());

        //如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_num && !$isDelete) {
            try {

                $telegram->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_approved),
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);

                if ($manuscript->status != ManuscriptStatus::APPROVED) {
                    $manuscript->status = ManuscriptStatus::APPROVED;

                    $channelMessageId = $this->sendChannelMessage($telegram, $botInfo, $manuscript);
                    $this->sendPostedByMessage($telegram, $manuscript, $botInfo,ManuscriptStatus::APPROVED);

                    $manuscript->message_id = $channelMessageId;

                    $manuscript->save();
                }

                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                Log::info('text:'.$text);
                return 'error';
            }
        }

        //如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_num && !$isDelete) {
            try {

                $telegram->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_reject),
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);

                if ($manuscript->status!=ManuscriptStatus::REJECTED){
                    $manuscript->status = ManuscriptStatus::REJECTED;
                    $manuscript->save();
                    $this->sendPostedByMessage($telegram, $manuscript, $botInfo,ManuscriptStatus::REJECTED);
                }

                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                Log::info('text:'.$text);
                return 'error';
            }
        }

        if ($manuscript->status == ManuscriptStatus::APPROVED && !$isDelete) {
            try {
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_approved),
                ]);
                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }

        if ($manuscript->status == ManuscriptStatus::REJECTED && !$isDelete) {
            try {
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_reject),
                ]);
                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }

        if ($manuscript->status == ManuscriptStatus::DELETE && $isDelete) {
            try {
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_delete),
                ]);
                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }
        return false;
    }
}
