<?php

namespace App\Services\CallBackQuery;

use App\Enums\KeyBoardData;
use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use App\Models\Complaint;
use App\Models\Manuscript;
use App\Services\SendTelegramMessageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait UpdateReviewGroupMessageButtonService
{

    use SendTelegramMessageService;

    public function update_review_group_message_button(
        Api $telegram,Bot $botInfo,$chatId,$messageId,Manuscript $manuscript,
        $review_approved_num,$review_reject_num,$approvedNum,$rejectNum,$isDelete=false)
    {
        $inline_keyboard_approved=KeyBoardData::REVIEW_GROUP_APPROVED;
        $inline_keyboard_approved['inline_keyboard'][0][0]['callback_data'] .= ":".$manuscript->id;
        $inline_keyboard_approved['inline_keyboard'][0][1]['url'] .= $manuscript->channel->name."/".$manuscript->message_id;
        $inline_keyboard_approved['inline_keyboard'][1][0]['callback_data'] .= ':'.$manuscript->id;

        $inline_keyboard_reject=KeyBoardData::REVIEW_GROUP_REJECT;
        $inline_keyboard_reject['inline_keyboard'][0][0]['callback_data'] .= ":".$manuscript->id;

        $inline_keyboard_delete = KeyBoardData::REVIEW_GROUP_DELETE;
        $inline_keyboard_delete['inline_keyboard'][0][0]['callback_data'] .= ":".$manuscript->id;

        $text=$manuscript->text;

        //自动关键词
        $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $text);
        // 加入匿名
        $text .= $this->addAnonymous($manuscript);
        //加入自定义尾部内容
        $text .= $this->addTailContent($botInfo->tail_content);

        $text .= $this->addReviewEndText($manuscript->approved,$manuscript->one_approved,$manuscript->reject, $manuscript->one_reject);

        //如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_approved_num && !$isDelete) {
            try {

                $params = [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_approved),
                    'parse_mode' => 'HTML',
                ];

                if ($manuscript->type!=Manuscript::TYPE_TEXT){
                    $params['caption']=$text;
                    $telegram->editMessageCaption($params);
                }else{
                    $params['text']=$text;
                    $telegram->editMessageText($params);
                }

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
                return 'error';
            }
        }

        //如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_reject_num && !$isDelete) {
            try {

                $params = [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_reject),
                    'parse_mode' => 'HTML',
                ];

                if ($manuscript->type!=Manuscript::TYPE_TEXT){
                    $params['caption']=$text;
                    $telegram->editMessageCaption($params);
                }else{
                    $params['text']=$text;
                    $telegram->editMessageText($params);
                }

                if ($manuscript->status!=ManuscriptStatus::REJECTED){
                    $manuscript->status = ManuscriptStatus::REJECTED;
                    $manuscript->save();
                    $this->sendPostedByMessage($telegram, $manuscript, $botInfo,ManuscriptStatus::REJECTED);
                }

                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
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

    public function update_review_group_complaint_message_button(
        Api $telegram,Bot $botInfo,$chatId,$messageId,Complaint $complaint,
        $review_approved_num,$review_reject_num,$approvedNum,$rejectNum,$isDelete=false)
    {
        $inline_keyboard_approved=KeyBoardData::REVIEW_GROUP_APPROVED;
        $inline_keyboard_approved['inline_keyboard'][0][0]['callback_data'] .= ":".$complaint->id;
        $inline_keyboard_approved['inline_keyboard'][0][1]['url'] .= $complaint->channel->name."/".$complaint->message_id;
        $inline_keyboard_approved['inline_keyboard'][1][0]['callback_data'] .= ':'.$complaint->id;

        $inline_keyboard_reject=KeyBoardData::REVIEW_GROUP_REJECT;
        $inline_keyboard_reject['inline_keyboard'][0][0]['callback_data'] .= ":".$complaint->id;

        $inline_keyboard_delete = KeyBoardData::REVIEW_GROUP_DELETE;
        $inline_keyboard_delete['inline_keyboard'][0][0]['callback_data'] .= ":".$complaint->id;

        $text=$complaint->text;

        //自动关键词
        $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $text);
        // 加入匿名
        $text .= $this->addAnonymous($complaint);
        //加入自定义尾部内容
        $text .= $this->addTailContent($botInfo->tail_content);

        $text .= $this->addReviewEndText($complaint->approved,$complaint->one_approved,$complaint->reject, $complaint->one_reject);

        //如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_approved_num && !$isDelete) {
            try {

                $params = [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_approved),
                    'parse_mode' => 'HTML',
                ];

                if ($complaint->type!=Manuscript::TYPE_TEXT){
                    $params['caption']=$text;
                    $telegram->editMessageCaption($params);
                }else{
                    $params['text']=$text;
                    $telegram->editMessageText($params);
                }

                if ($complaint->status != ManuscriptStatus::APPROVED) {
                    $complaint->status = ManuscriptStatus::APPROVED;

                    $channelMessageId = $this->sendChannelMessage($telegram, $botInfo, $complaint);
                    $this->sendPostedByMessage($telegram, $complaint, $botInfo,ManuscriptStatus::APPROVED);

                    $complaint->message_id = $channelMessageId;

                    $complaint->save();
                }

                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }

        //如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_reject_num && !$isDelete) {
            try {

                $params = [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_reject),
                    'parse_mode' => 'HTML',
                ];

                if ($complaint->type!=Manuscript::TYPE_TEXT){
                    $params['caption']=$text;
                    $telegram->editMessageCaption($params);
                }else{
                    $params['text']=$text;
                    $telegram->editMessageText($params);
                }

                if ($complaint->status!=ManuscriptStatus::REJECTED){
                    $complaint->status = ManuscriptStatus::REJECTED;
                    $complaint->save();
//                    $this->sendPostedByMessage($telegram, $complaint, $botInfo,ManuscriptStatus::REJECTED);
                }

                return true;
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);
                return 'error';
            }
        }

        if ($complaint->status == ManuscriptStatus::APPROVED && !$isDelete) {
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

        if ($complaint->status == ManuscriptStatus::REJECTED && !$isDelete) {
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

        if ($complaint->status == ManuscriptStatus::DELETE && $isDelete) {
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
