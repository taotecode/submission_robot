<?php

namespace App\Services\CallBackQuery;

use App\Enums\AuditorRole;
use App\Enums\InlineKeyBoardData;
use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Services\SendPostedByMessageService;
use App\Services\SendTelegramMessageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\User;

class ApprovedAndRejectedSubmissionService
{
    use AuditorRoleCheckService;
    use SendPostedByMessageService;
    use SendTelegramMessageService;
    use UpdateReviewGroupMessageButtonService;

    public function approved(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, CallbackQuery $callbackQuery): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        //机器人的审核数
        $review_approved_num = $botInfo->review_approved_num;
        $review_reject_num = $botInfo->review_reject_num;
        //稿件ID
        $manuscriptId = $manuscript->id;
        //通过人员名单
        $approved = $manuscript->approved;
        //通过人员数量
        $approvedNum = count($approved);
        //拒绝人员名单
        $reject = $manuscript->reject;
        //拒绝人员数量
        $rejectNum = count($reject);

        $inline_keyboard_approved = InlineKeyBoardData::$REVIEW_GROUP_APPROVED;
        $inline_keyboard_approved['inline_keyboard'][0][0]['callback_data'] .= ':'.$manuscript->id;
        $inline_keyboard_approved['inline_keyboard'][0][1]['url'] .= $manuscript->channel->name.'/'.$manuscript->message_id;
        $inline_keyboard_approved['inline_keyboard'][1][0]['callback_data'] .= ':'.$manuscript->id;

        if ($this->baseCheck($telegram, $callbackQuery->id, $from->id, $reviewGroup->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($telegram, $callbackQuery->id, $from->id, [
            AuditorRole::APPROVAL,
            AuditorRole::REJECTION,
        ]) !== true) {
            return 'ok';
        }

        if ($this->update_review_group_message_button($telegram, $botInfo, $chatId, $messageId, $manuscript, $review_approved_num, $review_reject_num, $approvedNum, $rejectNum) === true) {
            return 'ok';
        }

        //检查执行人是否在通过名单中
        $approvedIndex = containsSubarray($approved, $from->toArray(), 'id', 'key');
        //如果不在通过名单中，则添加
        if ($approvedIndex === false) {
            $approved[] = $from->toArray();
            $approvedNum++;
        } else {
            //如果在通过名单中，则删除
            unset($approved[$approvedIndex]);
            $approvedNum--;
        }
        //检查执行人是否在拒绝名单中
        $rejectIndex = containsSubarray($reject, $from->toArray(), 'id', 'key');
        //如果在拒绝名单中，则删除
        if ($rejectIndex !== false) {
            unset($reject[$rejectIndex]);
            $rejectNum--;
        }

        $text = $manuscript->text;

        $lexiconPath = null;
        if ($botInfo->is_auto_keyword == 1) {
            //检查是否有词库
            if (Storage::exists("public/lexicon_{$botInfo->id}.txt")) {
                $lexiconPath = storage_path("app/public/lexicon_{$botInfo->id}.txt");
            }
        }

        //自动关键词
        $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $text);
        // 加入匿名
        $text .= $this->addAnonymous($manuscript);
        //加入自定义尾部内容
        $text .= $this->addTailContent($botInfo->tail_content);

        $text .= $this->addReviewEndText($approved, $manuscript->one_approved, $reject, $manuscript->one_reject);

        // 如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_approved_num) {
            try {
                $manuscript->approved = $approved;
                $manuscript->reject = $reject;
                $manuscript->status = ManuscriptStatus::APPROVED;

                $params = [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_approved),
                    'parse_mode' => 'HTML',
                ];

                if ($manuscript->type != Manuscript::TYPE_TEXT) {
                    $params['caption'] = $text;
                    $telegram->editMessageCaption($params);
                } else {
                    $params['text'] = $text;
                    $telegram->editMessageText($params);
                }

                $channelMessageId = $this->sendChannelMessage($telegram, $botInfo, $manuscript);
                $this->sendPostedByMessage($telegram, $manuscript, $botInfo, ManuscriptStatus::APPROVED);

                $manuscript->message_id = $channelMessageId['message_id'];
                $manuscript->save();

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $inline_keyboard = InlineKeyBoardData::REVIEW_GROUP;

        $inline_keyboard['inline_keyboard'][0][0]['text'] .= "($approvedNum/$review_approved_num)";
        $inline_keyboard['inline_keyboard'][0][0]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][1]['text'] .= "($rejectNum/$review_reject_num)";
        $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][2]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][1][0]['callback_data'] .= ":$manuscriptId";
        $inline_keyboard['inline_keyboard'][1][1]['callback_data'] .= ":$manuscriptId";

        try {

            $manuscript->approved = $approved;
            $manuscript->reject = $reject;
            $manuscript->status = ManuscriptStatus::PENDING;
            $manuscript->save();

            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode($inline_keyboard),
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    public function rejected(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, CallbackQuery $callbackQuery): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        //机器人的审核数
        $review_approved_num = $botInfo->review_approved_num;
        $review_reject_num = $botInfo->review_reject_num;
        //稿件ID
        $manuscriptId = $manuscript->id;
        //通过人员名单
        $approved = $manuscript->approved;
        //通过人员数量
        $approvedNum = count($approved);
        //拒绝人员名单
        $reject = $manuscript->reject;
        //拒绝人员数量
        $rejectNum = count($reject);

        $inline_keyboard_reject = InlineKeyBoardData::$REVIEW_GROUP_REJECT;
        $inline_keyboard_reject['inline_keyboard'][0][0]['callback_data'] .= ':'.$manuscript->id;

        if ($this->baseCheck($telegram, $callbackQuery->id, $from->id, $reviewGroup->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($telegram, $callbackQuery->id, $from->id, [
            AuditorRole::APPROVAL,
            AuditorRole::REJECTION,
        ]) !== true) {
            return 'ok';
        }

        if ($this->update_review_group_message_button($telegram, $botInfo, $chatId, $messageId, $manuscript, $review_approved_num, $review_reject_num, $approvedNum, $rejectNum) === true) {
            return 'ok';
        }

        //检查执行人是否在拒绝名单中
        $rejectIndex = containsSubarray($reject, $from->toArray(), 'id', 'key');
        //如果不在拒绝名单中，则添加
        if ($rejectIndex === false) {
            $reject[] = $from->toArray();
            $rejectNum++;
        } else {
            //如果在拒绝名单中，则删除
            unset($reject[$rejectIndex]);
            $rejectNum--;
        }
        //检查执行人是否在通过名单中
        $approvedIndex = containsSubarray($approved, $from->toArray(), 'id', 'key');
        if ($approvedIndex !== false) {
            //如果在通过名单中，则删除
            unset($approved[$approvedIndex]);
            $approvedNum--;
        }

        $text = $manuscript->text;

        //自动关键词
        $text .= $this->addKeyWord($botInfo->is_auto_keyword, $botInfo->keyword, $botInfo->id, $text);
        // 加入匿名
        $text .= $this->addAnonymous($manuscript);
        //加入自定义尾部内容
        $text .= $this->addTailContent($botInfo->tail_content);

        $text .= $this->addReviewEndText($approved, $manuscript->one_approved, $reject, $manuscript->one_reject);

        // 如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_reject_num) {
            try {
                $manuscript->approved = $approved;
                $manuscript->reject = $reject;
                $manuscript->status = ManuscriptStatus::REJECTED;
                $manuscript->save();

                $params = [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($inline_keyboard_reject),
                    'parse_mode' => 'HTML',
                ];

                if ($manuscript->type != Manuscript::TYPE_TEXT) {
                    $params['caption'] = $text;
                    $telegram->editMessageCaption($params);
                } else {
                    $params['text'] = $text;
                    $telegram->editMessageText($params);
                }

                $this->sendPostedByMessage($telegram, $manuscript, $botInfo, ManuscriptStatus::REJECTED);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $inline_keyboard = InlineKeyBoardData::REVIEW_GROUP;

        $inline_keyboard['inline_keyboard'][0][0]['text'] .= "($approvedNum/$review_approved_num)";
        $inline_keyboard['inline_keyboard'][0][0]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][1]['text'] .= "($rejectNum/$review_reject_num)";
        $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][2]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][1][0]['callback_data'] .= ":$manuscriptId";
        $inline_keyboard['inline_keyboard'][1][1]['callback_data'] .= ":$manuscriptId";

        try {

            $manuscript->approved = $approved;
            $manuscript->reject = $reject;
            $manuscript->status = ManuscriptStatus::PENDING;
            $manuscript->save();

            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode($inline_keyboard),
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
