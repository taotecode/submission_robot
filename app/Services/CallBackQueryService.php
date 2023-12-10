<?php

namespace App\Services;

use App\Enums\KeyBoardData;
use App\Models\Auditor;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Models\ReviewGroupAuditor;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

class CallBackQueryService
{
    public string $cacheTag = 'start_submission';

    use SendChannelMessageService;
    use SendPostedByMessageService;

    public Manuscript $manuscriptModel;

    public function __construct(Manuscript $manuscriptModel)
    {
        $this->manuscriptModel = $manuscriptModel;
    }

    public function index($botInfo, Update $updateData, Api $telegram)
    {
        $chat = $updateData->getChat();
        $chatId = $chat->id;
        $messageId = $updateData->getMessage()->messageId;
        $callbackQuery = $updateData->callbackQuery;
        //执行人
        $from = $callbackQuery->from;
        $command = $callbackQuery->data;
        $replyToMessage = $callbackQuery->message->replyToMessage;
        $manuscriptId = null;
        $manuscript = null;

        $commandArray = explode(':', $command);
        if (count($commandArray) > 1) {
            $command = $commandArray[0];
            $manuscriptId = $commandArray[1];
            $manuscript = $this->manuscriptModel->find($manuscriptId);
        }

        switch ($command) {
            case 'text_approved_submission':
                $this->text_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, true, $callbackQuery);
                break;
            case 'text_reject_submission':
                $this->text_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, false, $callbackQuery);
                break;
            case 'text_approved_submission_quick':
                $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, true, $callbackQuery);
                break;
            case 'text_reject_submission_quick':
                $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, false, $callbackQuery);
                break;
            case 'private_message':
                $this->private_message($telegram, $botInfo, $manuscript, $chatId, $from, $callbackQuery);
                break;
            case 'approved_submission':
                $this->approvedOrRejectSubmission($telegram, true, $callbackQuery);
                break;
            case 'reject_submission':
                $this->approvedOrRejectSubmission($telegram, false, $callbackQuery);
                break;
        }
    }

    public function text_submission(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        //机器人的审核数
        $review_num = $botInfo->review_num;
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

        //如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_num) {
            try {
                $manuscript->status = 1;

                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_APPROVED),
                ]);

                $channelMessageId=$this->sendChannelMessage($telegram, $botInfo, $manuscript);
                $this->sendPostedByMessage($telegram, $manuscript, 1);

                $manuscript->message_id=$channelMessageId;

                $manuscript->save();

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        //如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_num) {
            try {
                $manuscript->status = 2;
                $manuscript->save();

                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_REJECT),
                ]);

                $this->sendPostedByMessage($telegram, $manuscript, 2);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $auditors = Auditor::where(['userId' => $from->id])->first();
        if (! $auditors) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您不是审核组成员，无法操作！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $reviewGroupAuditor = ReviewGroupAuditor::where(['review_group_id' => $reviewGroup->id, 'auditor_id' => $auditors->id])->first();
        if (! $reviewGroupAuditor) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您不是审核组成员，无法操作！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        //通过
        if ($isApproved) {
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
        } else {
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
        }

        // 如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_num) {
            try {
                $manuscript->approved = $approved;
                $manuscript->reject = $reject;
                $manuscript->status = 1;

                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_APPROVED),
                ]);

                $channelMessageId=$this->sendChannelMessage($telegram, $botInfo, $manuscript);
                $this->sendPostedByMessage($telegram, $manuscript, 1);

                $manuscript->message_id=$channelMessageId;
                $manuscript->save();

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        // 如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_num) {
            try {
                $manuscript->approved = $approved;
                $manuscript->reject = $reject;
                $manuscript->status = 2;
                $manuscript->save();

                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_REJECT),
                ]);

                $this->sendPostedByMessage($telegram, $manuscript, 2);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $inline_keyboard = KeyBoardData::REVIEW_GROUP;

        $inline_keyboard['inline_keyboard'][0][0]['text'] .= "($approvedNum/$review_num)";
        $inline_keyboard['inline_keyboard'][0][0]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][1]['text'] .= "($rejectNum/$review_num)";
        $inline_keyboard['inline_keyboard'][0][1]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][0][2]['callback_data'] .= ":$manuscriptId";

        $inline_keyboard['inline_keyboard'][1][0]['callback_data'] .= ":$manuscriptId";
        $inline_keyboard['inline_keyboard'][1][1]['callback_data'] .= ":$manuscriptId";

        try {

            $manuscript->approved = $approved;
            $manuscript->reject = $reject;
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

    private function private_message(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, CallbackQuery $callbackQuery): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        //检查有没有快捷操作权限
        $auditors = Auditor::where(['userId' => $from->id])->first();
        if (! $auditors) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您不是审核组成员，无法操作！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $reviewGroupAuditor = ReviewGroupAuditor::where(['review_group_id' => $reviewGroup->id, 'auditor_id' => $auditors->id])->first();
        if (! $reviewGroupAuditor) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您不是审核组成员，无法操作！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        if (! in_array(3, $auditors->role)) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您没有快捷操作权限！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $posted_by = $manuscript->posted_by;

        $inline_keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '通用用户名联系', 'url' => 'https://t.me/'],
                    ['text' => '客户端协议联系', 'url' => 'tg://openmessage?user_id='.$posted_by['id']],
                    ['text' => 'ID链接联系', 'url' => 'https://t.me/@id'.$posted_by['id']],
                ],
            ],
        ];

        $text = '昵称：'.$posted_by['first_name'].' '.$posted_by['last_name']."\r\nUID: `{$posted_by['id']}`";

        if (isset($posted_by['username'])) {
            $inline_keyboard['inline_keyboard'][0][0]['url'] = 'https://t.me/'.$posted_by['username'];
            $text .= "\r\n用户名：@".$posted_by['username'];
        } else {
            unset($inline_keyboard['inline_keyboard'][0][0]);
        }

        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => json_encode($inline_keyboard),
                'parse_mode' => 'markdown',
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    private function submission_quick(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery): string
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

        //如果通过人员数量大于等于审核数，则不再审核
        if ($approvedNum >= $review_num) {
            try {
                $manuscript->status = 1;

                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_APPROVED),
                ]);

                $channelMessageId=$this->sendChannelMessage($telegram, $botInfo, $manuscript);
                $this->sendPostedByMessage($telegram, $manuscript, 1);

                $manuscript->message_id=$channelMessageId;
                $manuscript->save();

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        //如果拒绝人员数量大于等于审核数，则不再审核
        if ($rejectNum >= $review_num) {
            try {
                $manuscript->status = 2;
                $manuscript->save();

                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_REJECT),
                ]);

                $this->sendPostedByMessage($telegram, $manuscript, 2);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        //检查有没有快捷操作权限
        $auditors = Auditor::where(['userId' => $from->id])->first();
        if (! $auditors) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您不是审核组成员，无法操作！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $reviewGroupAuditor = ReviewGroupAuditor::where(['review_group_id' => $reviewGroup->id, 'auditor_id' => $auditors->id])->first();
        if (! $reviewGroupAuditor) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您不是本审核组成员，无法操作！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        if (! in_array(1, $auditors->role) || ! in_array(2, $auditors->role)) {
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '您没有快捷操作权限！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        try {
            if ($isApproved) {
                $manuscript->one_approved = $from->toArray();
                $manuscript->status = 1;
                $channelMessageId=$this->sendChannelMessage($telegram, $botInfo, $manuscript);
                $this->sendPostedByMessage($telegram, $manuscript, 1);
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_APPROVED),
                ]);
                $manuscript->message_id=$channelMessageId;
                $manuscript->save();
            } else {
                $manuscript->one_reject = $from->toArray();
                $manuscript->status = 2;
                $manuscript->save();
                $this->sendPostedByMessage($telegram, $manuscript, 2);
                $telegram->editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(KeyBoardData::REVIEW_GROUP_REJECT),
                ]);
            }

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    private function approvedOrRejectSubmission(Api $telegram, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        try {
            if ($isApproved) {
                $text = '该稿件已通过审核！请勿再点击！';
            } else {
                $text = '该稿件已被拒绝！请勿再点击！';
            }
            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => $text,
                'show_alert' => true,
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
