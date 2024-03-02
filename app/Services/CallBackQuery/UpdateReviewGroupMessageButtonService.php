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

    public function update_review_group_message_button(Api $telegram,Bot $botInfo,$chatId,$messageId,Manuscript $manuscript,$review_num,$approvedNum,$rejectNum)
    {


        //如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_num) {
            try {
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_APPROVED),
                ]);

                if ($manuscript->status != ManuscriptStatus::APPROVED) {
                    $manuscript->status = ManuscriptStatus::APPROVED;

                    $channelMessageId = $this->sendChannelMessage($telegram, $botInfo, $manuscript);
                    $this->sendPostedByMessage($telegram, $manuscript, ManuscriptStatus::APPROVED);

                    $manuscript->message_id = $channelMessageId;

                    $manuscript->save();
                }

                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        //如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_num) {
            try {
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_REJECT),
                ]);

                if ($manuscript->status!=ManuscriptStatus::REJECTED){
                    $manuscript->status = ManuscriptStatus::REJECTED;
                    $manuscript->save();
                    $this->sendPostedByMessage($telegram, $manuscript, ManuscriptStatus::REJECTED);
                }

                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        if ($manuscript->status == ManuscriptStatus::DELETE) {
            try {
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_DELETE),
                ]);
                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }


//        if ($manuscript->status == ManuscriptStatus::APPROVED) {
//            try {
//                $telegram->editMessageReplyMarkup([
//                    'chat_id' => $chatId,
//                    'message_id' => $messageId,
//                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_APPROVED),
//                ]);
//                return true;
//            } catch (TelegramSDKException $telegramSDKException) {
//                Log::error($telegramSDKException);
//                return 'error';
//            }
//        }
//
//        if ($manuscript->status == ManuscriptStatus::REJECTED) {
//            try {
//                $telegram->editMessageReplyMarkup([
//                    'chat_id' => $chatId,
//                    'message_id' => $messageId,
//                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_REJECT),
//                ]);
//                return true;
//            } catch (TelegramSDKException $telegramSDKException) {
//                Log::error($telegramSDKException);
//                return 'error';
//            }
//        }
        return false;
    }
}
