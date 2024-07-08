<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Complaint;
use App\Models\Manuscript;
use App\Services\CallBackQuery\PrivateMessageService;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

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
            case 'approved_submission':
            case 'reject_submission':
            case 'approved_submission_quick':
            case 'reject_submission_quick':
            case 'approved_submission_button':
            case 'reject_submission_button':
            case 'delete_submission_message':
            case 'delete_white_list_user_submission_message':
            case 'delete_submission_message_success':
            case 'set_submission_user_type':
            case 'refresh_pending_manuscript_list':
            case 'show_pending_manuscript':
            case 'manuscript_search_show_link':
            case 'manuscript_search_page':
            case 'select_channel':
            case 'quick_submission':
            case 'forward_origin_select_Yes':
            case 'forward_origin_select_No':
            case 'forward_origin_select_restart':
            case 'forward_origin_input_cancel':
            case 'disable_message_preview_yes':
            case 'disable_message_preview_no':
            case 'disable_notification_yes':
            case 'disable_notification_no':
            case 'protect_content_yes':
            case 'protect_content_no':
                return (new \App\Services\CallBackQuery\SubmissionService())->index(
                    $telegram,$botInfo,$updateData, $command,$commandArray,$chat,$chatId,$messageId,$callbackQuery,$callbackQueryId,$message,$from,$replyToMessage,$manuscript,$manuscriptId
                );
                break;
            case 'approved_complaint':
                //                $this->approved_and_reject_complaint($telegram, $botInfo, $manuscriptId, $chatId, $from, $messageId, true, $callbackQuery);
                break;
            case 'private_message_start':
            case 'private_message_open_bot':
                return (new PrivateMessageService())->index(
                    $telegram,$botInfo,$updateData, $command,$commandArray,$chat,$chatId,$messageId,$callbackQuery,$callbackQueryId,$message,$from,$replyToMessage,$manuscript,$manuscriptId
                );
                break;
        }
    }
}
