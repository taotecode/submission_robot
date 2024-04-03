<?php

namespace App\Services\CallBackQuery;

use App\Enums\AuditorRole;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\User;

class SetSubmissionUserTypeService
{

    use AuditorRoleCheckService;

    public function setSubmissionUserType($telegram,Bot $botInfo, User $from,$callbackQuery,$commandArray): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;
        //设置投稿人类型
        if ($this->baseCheck($telegram, $callbackQuery->id, $from->id, $reviewGroup->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($telegram, $callbackQuery->id, $from->id, [
                AuditorRole::SET_SUBMISSION_USER_TYPE,
            ]) !== true) {
            return 'ok';
        }

        if (empty($commandArray[2])|| empty($commandArray[3])) {
            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => "参数错误！",
                'show_alert' => true,
            ]);
            return 'ok';
        }

        if (! in_array($commandArray[2], SubmissionUserType::getKey())) {
            Log::info('参数不存在！',[
                'commandArray'=>$commandArray,
                'key'=>SubmissionUserType::getKey(),
            ]);
            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => "参数不存在！",
                'show_alert' => true,
            ]);
            return 'ok';
        }

        $submissionUser=(new SubmissionUser())->where(['userId'=>$commandArray[3],'bot_id'=>$botInfo->id])->first();
        $submissionUser->type=$commandArray[2];
        $submissionUser->save();

        $telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => "设置成功！",
            'show_alert' => true,
        ]);
        return 'ok';
    }
}
