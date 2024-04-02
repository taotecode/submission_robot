<?php

namespace App\Services\CallBackQuery;

use App\Models\Auditor;
use App\Models\ReviewGroupAuditor;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

trait AuditorRoleCheckService
{

    /**
     * 检查用户是否是审核成员和审核组成员
     *
     * @param Api $telegram
     * @param int|string $callbackQueryId
     * @param int|string $userId
     * @param int|string $reviewGroupId
     * @param bool $isReply
     * @param string|int|null $messageId
     * @return bool|string
     */
    public function baseCheck(Api $telegram,int|string $callbackQueryId,int|string $userId,int|string $reviewGroupId,bool $isReply=false,string|int $messageId=null): bool|string
    {
        //检查用户是否是审核成员
        $auditors = Auditor::where(['userId' => $userId])->first();
        if (! $auditors) {
            try {
                if ($isReply){
                    $telegram->sendMessage([
                        'chat_id' => $callbackQueryId,
                        'reply_to_message_id' => $messageId,
                        'text' => '您不是审核成员，无法操作！',
                        'parse_mode' => 'HTML',
                    ]);
                }else{
                    $telegram->answerCallbackQuery([
                        'callback_query_id' => $callbackQueryId,
                        'text' => '您不是审核成员，无法操作！',
                        'show_alert' => true,
                    ]);
                }

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        //检查用户是否是审核群组的成员
        $reviewGroupAuditor = ReviewGroupAuditor::where(['review_group_id' => $reviewGroupId, 'auditor_id' => $auditors->id])->first();
        if (! $reviewGroupAuditor) {
            try {
                if ($isReply){
                    $telegram->sendMessage([
                        'chat_id' => $callbackQueryId,
                        'reply_to_message_id' => $messageId,
                        'text' => '您不是审核群组成员，无法操作！',
                        'parse_mode' => 'HTML',
                    ]);
                }else {
                    $telegram->answerCallbackQuery([
                        'callback_query_id' => $callbackQueryId,
                        'text' => '您不是审核群组成员，无法操作！',
                        'show_alert' => true,
                    ]);
                }

                return 'ok';
            } catch (TelegramSDKException $telegramSDKException) {
                Log::error($telegramSDKException);

                return 'error';
            }
        }

        return true;
    }

    /**
     * 检查用户的审核角色的权限
     *
     * @param Api $telegram
     * @param int|string $callbackQueryId
     * @param int|string $userId
     * @param array $roles
     * @param bool $isReply
     * @param string|int|null $messageId
     * @return bool|string
     */
    public function roleCheck(Api $telegram,int|string $callbackQueryId,int|string $userId,array $roles,bool $isReply=false,string|int $messageId=null): bool|string
    {
        $auditorsRole = Auditor::where(['userId' => $userId])->value('role');
        foreach ($roles as $role) {
            if (! in_array($role, $auditorsRole)) {
                try {
                    if ($isReply){
                        $telegram->sendMessage([
                            'chat_id' => $callbackQueryId,
                            'reply_to_message_id' => $messageId,
                            'text' => '您没有相关权限！无法操作！',
                            'parse_mode' => 'HTML',
                        ]);
                    }else{
                        $telegram->answerCallbackQuery([
                            'callback_query_id' => $callbackQueryId,
                            'text' => '您没有相关权限！无法操作！',
                            'show_alert' => true,
                        ]);
                    }
                    return 'ok';
                } catch (TelegramSDKException $telegramSDKException) {
                    Log::error($telegramSDKException);

                    return 'error';
                }
            }
        }
        return true;
    }
}
