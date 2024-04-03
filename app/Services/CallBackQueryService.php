<?php

namespace App\Services;

use App\Enums\KeyBoardData;
use App\Models\Auditor;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Models\ReviewGroupAuditor;
use App\Services\CallBackQuery\ApprovedAndRejectedSubmissionService;
use App\Services\CallBackQuery\DeleteSubmissionMessageService;
use App\Services\CallBackQuery\PendingManuscriptService;
use App\Services\CallBackQuery\PrivateMessageService;
use App\Services\CallBackQuery\QuickSubmissionService;
use App\Services\CallBackQuery\SetSubmissionUserTypeService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

class CallBackQueryService
{
    public string $cacheTag = 'start_submission';

    //    use SendChannelMessageService;
    use SendPostedByMessageService;
    use SendTelegramMessageService;

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
        $message = $updateData->getMessage();
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
            case 'approved_submission':
                $this->approved_and_rejected_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, true, $callbackQuery);
                break;
            case 'reject_submission':
                $this->approved_and_rejected_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, false, $callbackQuery);
                break;
            case 'approved_submission_quick':
                $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, true, $callbackQuery);
                break;
            case 'reject_submission_quick':
                $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, false, $callbackQuery);
                break;
            case 'private_message':
                $this->private_message($telegram, $botInfo, $manuscript, $chatId, $from, $callbackQuery);
                break;
            case 'approved_submission_button':
                $this->approvedOrRejectSubmission($telegram, true, $callbackQuery);
                break;
            case 'reject_submission_button':
                $this->approvedOrRejectSubmission($telegram, false, $callbackQuery);
                break;
            case 'delete_submission_message':
            case 'delete_white_list_user_submission_message':
                $this->deleteSubmissionMessage($telegram,$botInfo,$manuscript,$callbackQuery,$chatId,$messageId,$from);
                break;
            case 'delete_submission_message_success':
                $this->deleteSubmissionMessageSuccess($telegram,$callbackQuery);
                break;
            case 'set_submission_user_type':
                $this->setSubmissionUserType($telegram, $botInfo, $from, $callbackQuery,$commandArray,$manuscriptId,$manuscript,$chatId,$messageId);
                break;
            case 'refresh_pending_manuscript_list':
                $this->refreshPendingManuscriptList($telegram, $botInfo, $chatId, $messageId,$message,$callbackQuery->id);
                break;
        }
    }

    public function approved_and_rejected_submission(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        $textSubmissionService = new ApprovedAndRejectedSubmissionService();

        //通过
        if ($isApproved) {
            return $textSubmissionService->approved($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, $callbackQuery);
        } else {
            return $textSubmissionService->rejected($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, $callbackQuery);
        }
    }

    private function private_message(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, CallbackQuery $callbackQuery): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        return (new PrivateMessageService())->private_message($telegram, $callbackQuery, $from, $reviewGroup, $manuscript, $chatId);
    }

    private function submission_quick(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        return (new QuickSubmissionService())->quick_submission($telegram, $callbackQuery, $from, $botInfo, $manuscript, $chatId, $messageId, $isApproved);
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

    private function deleteSubmissionMessage(Api $telegram,$botInfo,?Manuscript $manuscript, ?CallbackQuery $callbackQuery,$chatId,$messageId,User $from): string
    {
        return (new DeleteSubmissionMessageService())->delete_submission_message($telegram, $botInfo, $manuscript, $callbackQuery, $chatId, $messageId, $from);
    }

    private function deleteSubmissionMessageSuccess(Api $telegram, ?CallbackQuery $callbackQuery): string
    {
        try {
            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => '该稿件已被删除！请勿再点击！',
                'show_alert' => true,
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }

    private function setSubmissionUserType(Api $telegram, $botInfo, User $from, ?CallbackQuery $callbackQuery,array $commandArray,$manuscriptId,$manuscript,$chatId,$messageId)
    {
        return (new SetSubmissionUserTypeService())->setSubmissionUserType($telegram, $botInfo, $from, $callbackQuery, $commandArray,$manuscriptId,$manuscript,$chatId,$messageId);
    }

    private function refreshPendingManuscriptList(Api $telegram, $botInfo, mixed $chatId, mixed $messageId,$message,$callbackQueryId)
    {
        return (new PendingManuscriptService())->refresh($telegram, $botInfo, $chatId, $messageId,$message,$callbackQueryId);
    }
}
