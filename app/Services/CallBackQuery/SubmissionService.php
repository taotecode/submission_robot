<?php

namespace App\Services\CallBackQuery;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use App\Services\CallBackQuery\Submission\ApprovedAndRejectedSubmissionService;
use App\Services\CallBackQuery\Submission\DeleteSubmissionMessageService;
use App\Services\CallBackQuery\Submission\ForwardOriginService;
use App\Services\CallBackQuery\Submission\ManuscriptSearchService;
use App\Services\CallBackQuery\Submission\PendingManuscriptService;
use App\Services\CallBackQuery\Submission\QuickSubmissionStatusService;
use App\Services\CallBackQuery\Submission\SelectChannelService;
use App\Services\CallBackQuery\Submission\SetSubmissionUserTypeService;
use App\Services\SendTelegramMessageService;
use App\Services\SubmissionService as SubmissionServiceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\User;

class SubmissionService
{
    use SendTelegramMessageService;

    public function index(
        $telegram, $botInfo,$updateData, $command,$commandArray,$chat,$chatId,$messageId,$callbackQuery,$callbackQueryId,$message,$from,$replyToMessage,$manuscript,$manuscriptId
    ){
        switch ($command) {
            case 'approved_submission':
                return $this->approved_and_rejected_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, true, $callbackQuery);
            case 'reject_submission':
                return $this->approved_and_rejected_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, false, $callbackQuery);
            case 'approved_submission_quick':
                return $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, true, $callbackQuery);
            case 'reject_submission_quick':
                return $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, false, $callbackQuery);
            case 'approved_submission_button':
                return $this->approvedOrRejectSubmission($telegram, true, $callbackQuery);
            case 'reject_submission_button':
                return $this->approvedOrRejectSubmission($telegram, false, $callbackQuery);
            case 'delete_submission_message':
            case 'delete_white_list_user_submission_message':
                return $this->deleteSubmissionMessage($telegram, $botInfo, $manuscript, $callbackQuery, $chatId, $messageId, $from);
            case 'delete_submission_message_success':
                return $this->deleteSubmissionMessageSuccess($telegram, $callbackQuery);
            case 'set_submission_user_type':
                return $this->setSubmissionUserType($telegram, $botInfo, $from, $callbackQuery, $commandArray, $manuscriptId, $manuscript, $chatId, $messageId);
            case 'select_channel':
                return $this->selectChannel($telegram, $botInfo, $chatId, $messageId, $callbackQueryId, $commandArray);
            case 'quick_submission':
                return $this->quick_submission($telegram, $botInfo, $updateData->getMessage()->replyToMessage);
            case 'forward_origin_select_Yes':
                return $this->forward_origin_select($telegram, $chatId, $messageId,1);
            case 'forward_origin_select_No':
                return $this->forward_origin_select($telegram, $chatId, $messageId,2);
            case 'forward_origin_select_restart':
                return $this->forward_origin_select($telegram, $chatId, $messageId,3);
            case 'forward_origin_input_cancel':
                return $this->forward_origin_select($telegram, $chatId, $messageId,4);
            case 'disable_message_preview_yes':
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'disable_message_preview_status','disable_message_preview_id',
                    get_config('submission.disable_message_preview_end_tip')
                );
            case 'disable_message_preview_no':
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'disable_message_preview_status','disable_message_preview_id',
                    get_config('submission.disable_message_preview_end_tip')
                );
            case 'disable_notification_yes':
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'disable_notification_status','disable_notification_id',
                    get_config('submission.disable_notification_end_tip')
                );
            case 'disable_notification_no':
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'disable_notification_status','disable_notification_id',
                    get_config('submission.disable_notification_end_tip')
                );
            case 'protect_content_yes':
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'protect_content_status','protect_content_id',
                    get_config('submission.protect_content_end_tip')
                );
            case 'protect_content_no':
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'protect_content_status','protect_content_id',
                    get_config('submission.protect_content_end_tip')
                );
            case 'refresh_pending_manuscript_list':
                return $this->refreshPendingManuscriptList($telegram, $botInfo, $chatId, $messageId, $message, $callbackQueryId);
            case 'show_pending_manuscript':
                return $this->showPendingManuscript($telegram, $botInfo, $manuscript);
            case 'manuscript_search_show_link':
                return $this->manuscriptSearchShowLink($telegram, $botInfo, $manuscript, $chatId);
            case 'manuscript_search_page':
                return $this->manuscriptSearchPage($telegram, $botInfo, $manuscript, $chatId, $messageId, $callbackQueryId, $commandArray);
        }
    }

    private function approved_and_rejected_submission(Api $telegram, Bot $botInfo, $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        $textSubmissionService = new ApprovedAndRejectedSubmissionService();
        //通过
        if ($isApproved) {
            return $textSubmissionService->approved($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, $callbackQuery);
        } else {
            return $textSubmissionService->rejected($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, $callbackQuery);
        }
    }

    private function submission_quick(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        return (new QuickSubmissionStatusService())->quick_submission($telegram, $callbackQuery, $from, $botInfo, $manuscript, $chatId, $messageId, $isApproved);
    }

    private function approvedOrRejectSubmission(Api $telegram, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        if ($isApproved) {
            $text = '该稿件已通过审核！请勿再点击！';
        } else {
            $text = '该稿件已被拒绝！请勿再点击！';
        }
        try {
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

    private function deleteSubmissionMessage(Api $telegram, $botInfo, ?Manuscript $manuscript, ?CallbackQuery $callbackQuery, $chatId, $messageId, User $from): string
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

    private function setSubmissionUserType(Api $telegram, $botInfo, User $from, ?CallbackQuery $callbackQuery, array $commandArray, $manuscriptId, $manuscript, $chatId, $messageId): string
    {
        return (new SetSubmissionUserTypeService())->setSubmissionUserType($telegram, $botInfo, $from, $callbackQuery, $commandArray, $manuscriptId, $manuscript, $chatId, $messageId);
    }

    private function selectChannel(Api $telegram, $botInfo, mixed $chatId, mixed $messageId, $callbackQueryId, array $commandArray): string
    {
        return (new SelectChannelService())->select($telegram, $botInfo, $chatId, $messageId, $callbackQueryId, $commandArray);
    }

    private function quick_submission(Api $telegram, Bot $botInfo, Message $message)
    {
        $objectType = $message->objectType();
        $chatId = $message->chat->id;

        Cache::tags(CacheKey::Submission . '.' . $chatId)->flush();
        //开启投稿服务标识
        Cache::tags(CacheKey::Submission . '.' . $chatId)->put($chatId, $message->chat->toArray(), now()->addDay());

        $submissionUser = (new SubmissionUser)->firstOrCreate([
            'bot_id' => $botInfo->id,
            'user_id' => $chatId,
        ], [
            'type' => SubmissionUserType::NORMAL,
            'bot_id' => $botInfo->id,
            'user_id' => $chatId,
            'user_data' => $message->chat->toArray(),
            'name' => get_posted_by($message->chat->toArray()),
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

        switch ($objectType) {
            case 'text':
                return (new SubmissionServiceService())->startUpdateByText($telegram, $botInfo, $chatId, $message->messageId, $message);
            case 'photo':
            case 'video':
            case 'audio':
                return (new SubmissionServiceService())->startUpdateByMedia($telegram, $botInfo, $chatId, $message->messageId, $message, $objectType);
        }
    }

    private function forward_origin_select(Api $telegram, $chatId, $messageId,$status): string
    {
        return match ($status) {
            1 => (new ForwardOriginService())->forward_origin_select_Yes($telegram, $chatId, $messageId),
            2 => (new ForwardOriginService())->forward_origin_select_No($telegram, $chatId, $messageId),
            3 => (new ForwardOriginService())->forward_origin_select_restart($telegram, $chatId, $messageId),
            default => (new ForwardOriginService())->forward_origin_input_cancel($telegram, $chatId, $messageId),
        };
    }

    private function selectCommonByYesOrNo(Api $telegram, $chatId, $messageId, $status, $cacheKey,$cacheMessageId, $text)
    {
        $cacheTag = CacheKey::Submission . '.' . $chatId;
        Cache::tags($cacheTag)->put($cacheKey, $status, now()->addDay());

        $this->sendTelegramMessage($telegram,'deleteMessage',[
            'chat_id' => $chatId,
            'message_id' => Cache::tags($cacheTag)->get($cacheMessageId),
        ]);

        return $this->sendTelegramMessage($telegram, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(KeyBoardData::$START_SUBMISSION),
        ]);
    }

    private function refreshPendingManuscriptList(Api $telegram, $botInfo, mixed $chatId, mixed $messageId, $message, $callbackQueryId): string
    {
        return (new PendingManuscriptService())->refresh($telegram, $botInfo, $chatId, $messageId, $message, $callbackQueryId);
    }

    private function showPendingManuscript(Api $telegram, $botInfo, ?Manuscript $manuscript): string
    {
        return (new PendingManuscriptService())->show($telegram, $botInfo, $manuscript);
    }

    private function manuscriptSearchShowLink(Api $telegram, $botInfo, ?Manuscript $manuscript, mixed $chatId): string
    {
        return (new ManuscriptSearchService())->link($telegram, $botInfo, $manuscript, $chatId);
    }

    private function manuscriptSearchPage(Api $telegram, $botInfo, ?Manuscript $manuscript, mixed $chatId, $messageId, $callbackQueryId, array $commandArray): string
    {
        return (new ManuscriptSearchService())->page($telegram, $botInfo, $manuscript, $chatId, $messageId, $callbackQueryId, $commandArray);
    }
}
