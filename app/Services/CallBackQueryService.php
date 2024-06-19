<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\KeyBoardData;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\Complaint;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use App\Services\CallBackQuery\ApprovedAndRejectedSubmissionService;
use App\Services\CallBackQuery\DeleteSubmissionMessageService;
use App\Services\CallBackQuery\ForwardOriginService;
use App\Services\CallBackQuery\ManuscriptSearchService;
use App\Services\CallBackQuery\PendingManuscriptService;
use App\Services\CallBackQuery\PrivateMessageService;
use App\Services\CallBackQuery\QuickSubmissionStatusService;
use App\Services\CallBackQuery\SelectChannelService;
use App\Services\CallBackQuery\SetSubmissionUserTypeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

class CallBackQueryService
{
    public string $cacheTag = 'start_submission';

    //    use SendChannelMessageService;
    use SendPostedByMessageService;
    use SendTelegramMessageService;

    public Manuscript $manuscriptModel;

    public Complaint $complaintModel;

    public function __construct()
    {
        $this->manuscriptModel = new Manuscript();
        $this->complaintModel = new Complaint();
    }

    public function index($botInfo, Update $updateData, Api $telegram)
    {
        $chat = $updateData->getChat();
        $chatId = $chat->id;
        $messageId = $updateData->getMessage()->messageId;
        $callbackQuery = $updateData->callbackQuery;
        $callbackQueryId = $callbackQuery->id;
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
                $this->deleteSubmissionMessage($telegram, $botInfo, $manuscript, $callbackQuery, $chatId, $messageId, $from);
                break;
            case 'delete_submission_message_success':
                $this->deleteSubmissionMessageSuccess($telegram, $callbackQuery);
                break;
            case 'set_submission_user_type':
                $this->setSubmissionUserType($telegram, $botInfo, $from, $callbackQuery, $commandArray, $manuscriptId, $manuscript, $chatId, $messageId);
                break;
            case 'refresh_pending_manuscript_list':
                $this->refreshPendingManuscriptList($telegram, $botInfo, $chatId, $messageId, $message, $callbackQueryId);
                break;
            case 'show_pending_manuscript':
                $this->showPendingManuscript($telegram, $botInfo, $manuscript);
                break;
            case 'manuscript_search_show_link':
                $this->manuscriptSearchShowLink($telegram, $botInfo, $manuscript, $chatId);
                break;
            case 'manuscript_search_page':
                $this->manuscriptSearchPage($telegram, $botInfo, $manuscript, $chatId, $messageId, $callbackQueryId, $commandArray);
                break;
            case 'select_channel':
                $this->selectChannel($telegram, $botInfo, $chatId, $messageId, $callbackQueryId, $commandArray);
                break;
            case 'approved_complaint':
                //                $this->approved_and_reject_complaint($telegram, $botInfo, $manuscriptId, $chatId, $from, $messageId, true, $callbackQuery);
                break;
            case 'quick_submission':
                $this->quick_submission($telegram, $botInfo, $updateData->getMessage()->replyToMessage);
                break;
            case 'forward_origin_select_Yes':
                $this->forward_origin_select_Yes($telegram, $chatId, $messageId);
                break;
            case 'forward_origin_select_No':
                $this->forward_origin_select_No($telegram, $chatId, $messageId);
                break;
            case 'forward_origin_select_restart':
                $this->forward_origin_select_restart($telegram, $chatId, $messageId);
                break;
            case 'forward_origin_input_cancel':
                $this->forward_origin_input_cancel($telegram, $chatId, $messageId);
                break;
            case 'disable_message_preview_yes':
                $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'disable_message_preview_status','disable_message_preview_id',
                    get_config('submission.disable_message_preview_end_tip')
                );
                break;
            case 'disable_message_preview_no':
                $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'disable_message_preview_status','disable_message_preview_id',
                    get_config('submission.disable_message_preview_end_tip')
                );
                break;
            case 'disable_notification_yes':
                $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'disable_notification_status','disable_notification_id',
                    get_config('submission.disable_notification_end_tip')
                );
                break;
            case 'disable_notification_no':
                $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'disable_notification_status','disable_notification_id',
                    get_config('submission.disable_notification_end_tip')
                );
                break;
            case 'protect_content_yes':
                $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'protect_content_status','protect_content_id',
                    get_config('submission.protect_content_end_tip')
                );
                break;
            case 'protect_content_no':
                $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'protect_content_status','protect_content_id',
                    get_config('submission.protect_content_end_tip')
                );
                break;
        }
    }

    public function approved_and_rejected_submission(Api $telegram, Bot $botInfo, $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        $textSubmissionService = new ApprovedAndRejectedSubmissionService();

        //        $complaint = $this->complaintModel->find($manuscriptId);

        //通过
        if ($isApproved) {
            //            return $textSubmissionService->approved($telegram, $botInfo, $complaint, $chatId, $from, $messageId, $callbackQuery);
            return $textSubmissionService->approved($telegram, $botInfo, $manuscript, $chatId, $from, $messageId, $callbackQuery);
        } else {
            //            return $textSubmissionService->rejected($telegram, $botInfo, $complaint, $chatId, $from, $messageId, $callbackQuery);
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
        return (new QuickSubmissionStatusService())->quick_submission($telegram, $callbackQuery, $from, $botInfo, $manuscript, $chatId, $messageId, $isApproved);
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

    private function setSubmissionUserType(Api $telegram, $botInfo, User $from, ?CallbackQuery $callbackQuery, array $commandArray, $manuscriptId, $manuscript, $chatId, $messageId)
    {
        return (new SetSubmissionUserTypeService())->setSubmissionUserType($telegram, $botInfo, $from, $callbackQuery, $commandArray, $manuscriptId, $manuscript, $chatId, $messageId);
    }

    private function refreshPendingManuscriptList(Api $telegram, $botInfo, mixed $chatId, mixed $messageId, $message, $callbackQueryId)
    {
        return (new PendingManuscriptService())->refresh($telegram, $botInfo, $chatId, $messageId, $message, $callbackQueryId);
    }

    private function showPendingManuscript(Api $telegram, $botInfo, ?Manuscript $manuscript)
    {
        return (new PendingManuscriptService())->show($telegram, $botInfo, $manuscript);
    }

    private function manuscriptSearchShowLink(Api $telegram, $botInfo, ?Manuscript $manuscript, mixed $chatId)
    {
        return (new ManuscriptSearchService())->link($telegram, $botInfo, $manuscript, $chatId);
    }

    private function manuscriptSearchPage(Api $telegram, $botInfo, ?Manuscript $manuscript, mixed $chatId, $messageId, $callbackQueryId, array $commandArray)
    {
        return (new ManuscriptSearchService())->page($telegram, $botInfo, $manuscript, $chatId, $messageId, $callbackQueryId, $commandArray);
    }

    private function selectChannel(Api $telegram, $botInfo, mixed $chatId, mixed $messageId, $callbackQueryId, array $commandArray)
    {
        return (new SelectChannelService())->select($telegram, $botInfo, $chatId, $messageId, $callbackQueryId, $commandArray);
    }

    public function approved_and_reject_complaint(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId, bool $isApproved, CallbackQuery $callbackQuery)
    {

    }

    public function quick_submission(Api $telegram, Bot $botInfo, Message $message)
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
                return (new SubmissionService())->startUpdateByText($telegram, $botInfo, $chatId, $message->messageId, $message);
            case 'photo':
            case 'video':
            case 'audio':
                return (new SubmissionService())->startUpdateByMedia($telegram, $botInfo, $chatId, $message->messageId, $message, $objectType);
                break;
        }
    }

    private function forward_origin_select_Yes(Api $telegram, $chatId, $messageId)
    {
        return (new ForwardOriginService())->forward_origin_select_Yes($telegram, $chatId, $messageId);
    }

    private function forward_origin_select_No(Api $telegram, $chatId, $messageId)
    {
        return (new ForwardOriginService())->forward_origin_select_No($telegram, $chatId, $messageId);
    }

    private function forward_origin_select_restart(Api $telegram, $chatId, $messageId)
    {
        return (new ForwardOriginService())->forward_origin_select_restart($telegram, $chatId, $messageId);
    }

    private function forward_origin_input_cancel(Api $telegram, mixed $chatId, mixed $messageId)
    {
        return (new ForwardOriginService())->forward_origin_input_cancel($telegram, $chatId, $messageId);
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
}
