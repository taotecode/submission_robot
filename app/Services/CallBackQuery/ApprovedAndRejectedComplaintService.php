<?php

namespace App\Services\CallBackQuery;

use App\Enums\AuditorRole;
use App\Models\Bot;
use App\Models\Complaint;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\User;

class ApprovedAndRejectedComplaintService
{
    public function approved(Api $telegram, Bot $botInfo, Complaint $complaint, $chatId, User $from, $messageId, CallbackQuery $callbackQuery)
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;

        //机器人的审核数
        $review_approved_num = $botInfo->review_approved_num;
        $review_reject_num = $botInfo->review_reject_num;
        //稿件ID
        $complaintId = $complaint->id;
        //通过人员名单
        $approved = $complaint->approved;
        //通过人员数量
        $approvedNum = count($approved);
        //拒绝人员名单
        $reject = $complaint->reject;
        //拒绝人员数量
        $rejectNum = count($reject);

        if ($this->baseCheck($telegram, $callbackQuery->id, $from->id, $reviewGroup->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($telegram, $callbackQuery->id, $from->id, [
            AuditorRole::APPROVAL,
            AuditorRole::REJECTION,
        ]) !== true) {
            return 'ok';
        }
    }
}
