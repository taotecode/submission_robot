<?php

namespace App\Services\CallBackQuery\Submission;

use App\Enums\AuditorRole;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\SubmissionUser;
use App\Services\CallBackQuery\AuditorRoleCheckService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\User;

class SetSubmissionUserTypeService
{
    use AuditorRoleCheckService;

    public function setSubmissionUserType(Api $telegram, Bot $botInfo, User $from, $callbackQuery, $commandArray, $manuscriptId, $manuscript, $chatId, $messageId): string
    {
        //获取审核群组信息
        $reviewGroup = $botInfo->review_group;
        //设置投稿人类型
        if ($this->baseCheck($telegram, $callbackQuery->id, $from->id, $reviewGroup->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($telegram, $callbackQuery->id, $from->id, [
            AuditorRole::SET_SUBMISSION_USER_TYPE,
                AuditorRole::ADD_BLACK,
        ]) !== true) {
            return 'ok';
        }

        if (! isset($commandArray[2]) || empty($commandArray[3])) {
            Log::info('参数错误！', [
                'commandArray' => $commandArray,
            ]);
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '参数错误！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        if (! in_array($commandArray[2], SubmissionUserType::getKey())) {
            Log::info('参数不存在！', [
                'commandArray' => $commandArray,
                'key' => SubmissionUserType::getKey(),
            ]);
            try {
                $telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '参数不存在！',
                    'show_alert' => true,
                ]);

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        $submissionUser = (new SubmissionUser())->where(['user_id' => $commandArray[3], 'bot_id' => $botInfo->id])->first();
        $submissionUser->type = $commandArray[2];
        $submissionUser->save();

        $submissionUsers = $manuscript->posted_by;

        $text = '用户ID：<code>'.$submissionUsers['id']."</code> \r\n";
        if (! empty($submissionUsers['username'])) {
            $text .= '用户名：<code>'.$submissionUsers['username']."</code> \r\n";
        }

        if (! empty($submissionUsers['first_name'])) {
            $text .= '姓名：<code>'.$submissionUsers['first_name']."</code> \r\n";
        }

        if (! empty($submissionUsers['last_name'])) {
            $text .= '姓氏：<code>'.$submissionUsers['last_name']."</code> \r\n";
        }

        $text .= '用户身份：<code>'.SubmissionUserType::MAP[$submissionUser->type]."</code> \r\n";

        $inline_keyboard = [
            'inline_keyboard' => [
            ],
        ];

        foreach (SubmissionUserType::MAP as $key => $value) {
            if ($key == $submissionUser->type) {
                continue;
            }
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => '设置为'.$value.'用户', 'callback_data' => 's_r_g_m_s_u_type:'.$manuscriptId.':'.$key.':'.$submissionUser->userId],
            ];
        }

        try {
            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($inline_keyboard),
            ]);

            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => '设置成功！',
                'show_alert' => false,
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}
