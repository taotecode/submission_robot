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
            case 's_r_g_m_r_approved'://submission_review_group_manuscript_review_approved；审核群组的稿件审核通过
                return $this->approved_and_rejected_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId,$message, true, $callbackQuery);
            case 's_r_g_m_r_reject'://submission_review_group_manuscript_review_reject；审核群组的稿件审核拒绝
                return $this->approved_and_rejected_submission($telegram, $botInfo, $manuscript, $chatId, $from, $messageId,$message, false, $callbackQuery);
            case 's_r_g_m_r_approved_quick'://submission_review_group_manuscript_review_approved_quick；审核群组的稿件快速审核通过
                return $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId,$message, true, $callbackQuery);
            case 's_r_g_m_r_reject_quick'://submission_review_group_manuscript_review_reject_quick；审核群组的稿件快速审核拒绝
                return $this->submission_quick($telegram, $botInfo, $manuscript, $chatId, $from, $messageId,$message, false, $callbackQuery);
            case 's_r_g_m_e_approved'://submission_review_group_manuscript_end_approved；审核群组的稿件审核结束-通过
                return $this->approvedOrRejectSubmission($telegram, true, $callbackQuery);
            case 's_r_g_m_e_reject'://submission_review_group_manuscript_end_reject；审核群组的稿件审核结束-拒绝
                return $this->approvedOrRejectSubmission($telegram, false, $callbackQuery);
            case 's_r_g_m_e_del_m'://submission_review_group_manuscript_end_delete_message；审核群组的稿件审核结束-删除消息
            case 's_r_g_m_e_del_w_u_m'://submission_review_group_manuscript_end_delete_white_user_message；审核群组的稿件审核结束-删除白名单用户投稿
                return $this->deleteSubmissionMessage($telegram, $botInfo, $manuscript, $callbackQuery, $chatId, $messageId, $from);
            case 's_r_g_m_e_del_m_s'://submission_review_group_manuscript_end_delete_message_success；审核群组的稿件审核结束-删除消息成功
                return $this->deleteSubmissionMessageSuccess($telegram, $callbackQuery);
            case 's_r_g_m_s_u_type'://submission_review_group_manuscript_set_user_type；设置投稿人身份
                return $this->setSubmissionUserType($telegram, $botInfo, $from, $callbackQuery, $commandArray, $manuscriptId, $manuscript, $chatId, $messageId);
            case 's_r_g_m_p_m_r_list'://submission_review_group_manuscript_pending_manuscript_refresh_list；审核群组的稿件待审核列表
                return $this->refreshPendingManuscriptList($telegram, $botInfo, $chatId, $messageId, $message, $callbackQueryId);
            case 's_r_g_m_p_m_show'://submission_review_group_manuscript_pending_manuscript_show；审核群组的稿件待审核详情
                return $this->showPendingManuscript($telegram, $botInfo, $manuscript);


            case 's_p_m_s_channel'://submission_private_manuscript_set_channel；投稿-私聊-设置发布频道
                return $this->selectChannel($telegram, $botInfo, $chatId, $messageId, $callbackQueryId, $commandArray);
            case 's_p_q_submission'://submission_private_quick_submission；投稿-私聊-快速投稿
                return $this->quick_submission($telegram, $botInfo, $updateData->getMessage()->replyToMessage);
            case 's_p_m_s_f_o_yes'://submission_private_manuscript_set_forward_origin_yes；投稿-私聊-设置转发来源-是
                return $this->forward_origin_select($telegram, $callbackQueryId, $chatId, $messageId,1);
            case 's_p_m_s_f_o_no'://submission_private_manuscript_set_forward_origin_no；投稿-私聊-设置转发来源-否
                return $this->forward_origin_select($telegram, $callbackQueryId, $chatId, $messageId,2);
            case 's_p_m_s_f_o_restart'://submission_private_manuscript_set_forward_origin_restart；投稿-私聊-设置转发来源-重置
                return $this->forward_origin_select($telegram, $callbackQueryId, $chatId, $messageId,3);
            case 's_p_m_s_f_o_i_cancel'://submission_private_manuscript_set_forward_origin_input_cancel；投稿-私聊-设置转发来源-取消输入
                return $this->forward_origin_select($telegram, $callbackQueryId, $chatId, $messageId,4);
            case 's_p_m_s_d_m_p_yes'://submission_private_manuscript_set_disable_message_preview_yes；投稿-私聊-消息预览-是
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'disable_message_preview_status','disable_message_preview_id',
                    get_config('submission.disable_message_preview_end_tip')
                );
            case 's_p_m_s_d_m_p_no'://submission_private_manuscript_set_disable_message_preview_no；投稿-私聊-消息预览-否
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'disable_message_preview_status','disable_message_preview_id',
                    get_config('submission.disable_message_preview_end_tip')
                );
            case 's_p_m_s_d_n_yes'://submission_private_manuscript_set_disable_notification_yes；投稿-私聊-消息通知-是
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'disable_notification_status','disable_notification_id',
                    get_config('submission.disable_notification_end_tip')
                );
            case 's_p_m_s_d_n_no'://submission_private_manuscript_set_disable_notification_no；投稿-私聊-消息通知-否
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'disable_notification_status','disable_notification_id',
                    get_config('submission.disable_notification_end_tip')
                );
            case 's_p_m_s_p_c_yes'://submission_private_manuscript_set_protect_content_no；投稿-私聊-保护内容-是
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    1,'protect_content_status','protect_content_id',
                    get_config('submission.protect_content_end_tip')
                );
            case 's_p_m_s_p_c_no'://submission_private_manuscript_set_protect_content_no；投稿-私聊-保护内容-否
                return $this->selectCommonByYesOrNo($telegram, $chatId, $messageId,
                    0,'protect_content_status','protect_content_id',
                    get_config('submission.protect_content_end_tip')
                );


            case 's_c_c_m_s_page'://submission_common_command_manuscript_search_page；投稿-公共-命令-稿件搜索-分页
                return $this->manuscriptSearchPage($telegram, $botInfo, $manuscript, $chatId, $messageId, $callbackQueryId, $commandArray);
            case 's_c_c_m_s_show'://submission_common_command_manuscript_show；投稿-公共-命令-稿件搜索-稿件详情
                return $this->manuscriptSearchShowLink($telegram, $botInfo, $manuscript, $chatId);
        }
    }

    private function approved_and_rejected_submission(Api $telegram, Bot $botInfo, $manuscript, $chatId, User $from, $messageId,$message, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        $textSubmissionService = new ApprovedAndRejectedSubmissionService();
        //通过
        if ($isApproved) {
            return $textSubmissionService->approved($telegram, $botInfo, $manuscript, $chatId, $from, $messageId,$message, $callbackQuery);
        } else {
            return $textSubmissionService->rejected($telegram, $botInfo, $manuscript, $chatId, $from, $messageId,$message, $callbackQuery);
        }
    }

    private function submission_quick(Api $telegram, Bot $botInfo, Manuscript $manuscript, $chatId, User $from, $messageId,$message, bool $isApproved, CallbackQuery $callbackQuery): string
    {
        return (new QuickSubmissionStatusService())->quick_submission($telegram, $callbackQuery, $from, $botInfo, $manuscript, $chatId, $messageId,$message, $isApproved);
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

    private function forward_origin_select(Api $telegram, $callbackQueryId, $chatId, $messageId,$status): string
    {
        return match ($status) {
            1 => (new ForwardOriginService())->forward_origin_select_Yes($telegram, $chatId, $messageId),
            2 => (new ForwardOriginService())->forward_origin_select_No($telegram, $chatId, $messageId),
            3 => (new ForwardOriginService())->forward_origin_select_restart($telegram, $chatId, $messageId),
            default => (new ForwardOriginService())->forward_origin_input_cancel($telegram, $callbackQueryId, $chatId, $messageId),
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
