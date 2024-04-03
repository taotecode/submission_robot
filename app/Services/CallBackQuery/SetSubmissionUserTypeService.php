<?php

namespace App\Services\CallBackQuery;

use App\Enums\AuditorRole;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\User;

class SetSubmissionUserTypeService
{

    use AuditorRoleCheckService;

    public function setSubmissionUserType(Api $telegram,Bot $botInfo, User $from,$callbackQuery,$commandArray,$manuscriptId,$manuscript,$chatId,$messageId): string
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

        $submissionUsers=$manuscript->posted_by;

        $text="用户ID：<code>".$submissionUsers['id']."</code> \r\n";
        if (!empty($submissionUsers['username'])){
            $text.="用户名：<code>".$submissionUsers['username']."</code> \r\n";
        }

        if (!empty($submissionUsers['first_name'])) {
            $text .= "姓名：<code>" . $submissionUsers['first_name'] . "</code> \r\n";
        }

        if (!empty($submissionUsers['last_name'])) {
            $text .= "姓氏：<code>" . $submissionUsers['last_name']. "</code> \r\n";
        }

        $text.="用户身份：<code>".SubmissionUserType::MAP[$submissionUser->type]."</code> \r\n";

        $inline_keyboard=[
            'inline_keyboard' => [
            ],
        ];

        foreach (SubmissionUserType::MAP as $key=>$value){
            if ($key==$submissionUser->type){
                continue;
            }
            $inline_keyboard['inline_keyboard'][]=[
                ['text' => '设置为'.$value.'用户', 'callback_data' => 'set_submission_user_type:'.$manuscriptId.':'.$key.':'.$submissionUser->userId],
            ];
        }

        $telegram->editMessageReplyMarkup([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => json_encode($inline_keyboard),
        ]);

        $telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        $telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => "设置成功！",
            'show_alert' => true,
        ]);
        return 'ok';
    }
}
