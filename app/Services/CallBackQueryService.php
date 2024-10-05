<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Manuscript;
use App\Services\CallBackQuery\PrivateMessageService;
use App\Services\CallBackQuery\SettingsServices;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class CallBackQueryService
{
    public string $cacheTag = 'start_submission';

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
            //投稿
            case 's_r_g_m_r_approved'://submission_review_group_manuscript_review_approved；审核群组的稿件审核通过
            case 's_r_g_m_r_reject'://submission_review_group_manuscript_review_reject；审核群组的稿件审核拒绝
            case 's_r_g_m_r_approved_quick'://submission_review_group_manuscript_review_approved_quick；审核群组的稿件快速审核通过
            case 's_r_g_m_r_reject_quick'://submission_review_group_manuscript_review_reject_quick；审核群组的稿件快速审核拒绝
            case 's_r_g_m_e_approved'://submission_review_group_manuscript_end_approved；审核群组的稿件审核结束-通过
            case 's_r_g_m_e_reject'://submission_review_group_manuscript_end_reject；审核群组的稿件审核结束-拒绝
            case 's_r_g_m_e_del_m'://submission_review_group_manuscript_end_delete_message；审核群组的稿件审核结束-删除消息
            case 's_r_g_m_e_del_w_u_m'://submission_review_group_manuscript_end_delete_white_user_message；审核群组的稿件审核结束-删除白名单用户投稿
            case 's_r_g_m_e_del_m_s'://submission_review_group_manuscript_end_delete_message_success；审核群组的稿件审核结束-删除消息成功
            case 's_r_g_m_s_u_type'://submission_review_group_manuscript_set_user_type；设置投稿人身份
            case 's_r_g_m_p_m_r_list'://submission_review_group_manuscript_pending_manuscript_refresh_list；审核群组的稿件待审核列表
            case 's_r_g_m_p_m_show'://submission_review_group_manuscript_pending_manuscript_show；审核群组的稿件待审核详情

            case 's_p_m_s_channel'://submission_private_manuscript_set_channel；投稿-私聊-设置发布频道
            case 's_p_q_submission'://submission_private_quick_submission；投稿-私聊-快速投稿
            case 's_p_m_s_f_o_yes'://submission_private_manuscript_set_forward_origin_yes；投稿-私聊-设置转发来源-是
            case 's_p_m_s_f_o_no'://submission_private_manuscript_set_forward_origin_no；投稿-私聊-设置转发来源-否
            case 's_p_m_s_f_o_restart'://submission_private_manuscript_set_forward_origin_restart；投稿-私聊-设置转发来源-重置
            case 's_p_m_s_f_o_i_cancel'://submission_private_manuscript_set_forward_origin_input_cancel；投稿-私聊-设置转发来源-取消输入
            case 's_p_m_s_d_m_p_yes'://submission_private_manuscript_set_disable_message_preview_yes；投稿-私聊-消息预览-是
            case 's_p_m_s_d_m_p_no'://submission_private_manuscript_set_disable_message_preview_no；投稿-私聊-消息预览-否
            case 's_p_m_s_d_n_yes'://submission_private_manuscript_set_disable_notification_yes；投稿-私聊-消息通知-是
            case 's_p_m_s_d_n_no'://submission_private_manuscript_set_disable_notification_no；投稿-私聊-消息通知-否
            case 's_p_m_s_p_c_yes'://submission_private_manuscript_set_protect_content_no；投稿-私聊-保护内容-是
            case 's_p_m_s_p_c_no'://submission_private_manuscript_set_protect_content_no；投稿-私聊-保护内容-否

            case 's_c_c_m_s_page'://submission_common_command_manuscript_search_page；投稿-公共-命令-稿件搜索-分页
            case 's_c_c_m_s_show'://submission_common_command_manuscript_show；投稿-公共-命令-稿件搜索-稿件详情
                return (new \App\Services\CallBackQuery\SubmissionService())->index(
                    $telegram,$botInfo,$updateData, $command,$commandArray,$chat,$chatId,$messageId,$callbackQuery,$callbackQueryId,$message,$from,$replyToMessage,$manuscript,$manuscriptId
                );
            case 'approved_complaint':
                //                $this->approved_and_reject_complaint($telegram, $botInfo, $manuscriptId, $chatId, $from, $messageId, true, $callbackQuery);
                break;
            case 'private_message_start':
            case 'private_message_open_bot':
                return (new PrivateMessageService())->index(
                    $telegram,$botInfo,$updateData, $command,$commandArray,$chat,$chatId,$messageId,$callbackQuery,$callbackQueryId,$message,$from,$replyToMessage,$manuscript,$manuscriptId
                );
            case 'c_c_s_anonymous'://common_command_setting_anonymous；公共-命令-设置-匿名
            case 'c_c_s_d_m_p'://common_command_setting_disable_message_preview；公共-命令-设置-消息预览
            case 'c_c_s_d_n'://common_command_setting_disable_notification；公共-命令-设置-消息通知
            case 's_p_m_s_f_o'://common_command_setting_forward_origin；公共-命令-设置-转发来源
                return (new SettingsServices())->index(
                    $telegram,$botInfo,$updateData, $command,$commandArray,$chat,$chatId,$messageId,$callbackQuery,$callbackQueryId,$message,$from,$replyToMessage
                );
            default:
                return 'error';
        }
    }
}
