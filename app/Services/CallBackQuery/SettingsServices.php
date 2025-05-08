<?php

namespace App\Services\CallBackQuery;

use App\Admin\Repositories\BotUser as BotUserRepository;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class SettingsServices
{
    public function index(Api $telegram, $botInfo, $command, $commandArray, $chatId, $messageId, $callbackQueryId): bool|string
    {
        if ($command==='my_setting'){
            return $this->my_setting($telegram, $botInfo, $chatId, $messageId, $callbackQueryId);
        }
        $status = $commandArray[2];
        $settingsMap = [
            'my_setting_anonymous' => 'is_anonymous',
            'my_setting_disable_message_preview' => 'is_link_preview',
            'my_setting_disable_notification' => 'is_disable_notification',
            'my_setting_forward_origin' => 'is_protect_content'
        ];

        if (!isset($settingsMap[$command])) {
            return 'error';
        }

        $type = $settingsMap[$command];
        return $this->updateSetting($telegram, $botInfo, $type, $status, $chatId, $messageId, $callbackQueryId);
    }

    private function my_setting(Api $telegram, $botInfo, $chatId, $messageId, $callbackQueryId): bool|string
    {
        $botUser=(new BotUserRepository())->findInfo($botInfo->id,$chatId);
        $replyMarkup = json_encode($this->createReplyMarkup($botUser));
        try {
            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => '我的个人设置',
                'reply_markup' => $replyMarkup,
            ]);
            return $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '加载完成',
                'show_alert' => false,
            ]);
        } catch (TelegramSDKException $e) {
            Log::error($e);
            return "error";
        }
    }

    private function updateSetting(Api $telegram, $botInfo, $type, $status, $chatId, $messageId, $callbackQueryId): bool|string
    {
        // 更新信息
        $botUser=(new BotUserRepository())->findInfo($botInfo->id,$chatId);
        $botUser->$type = $status;
        $botUser->save();

        $replyMarkup = json_encode($this->createReplyMarkup($botUser));

        try {
            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => $replyMarkup,
            ]);

            return $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '设置已变更',
                'show_alert' => false,
            ]);
        } catch (TelegramSDKException $e) {
            Log::error($e);
            return "error";
        }
    }

    private function createReplyMarkup($botUser): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '投稿身份【' . ($botUser['is_anonymous'] ? '匿名' : '不匿名') . '】', 'callback_data' => 'c_c_s_anonymous:null:' . ($botUser['is_anonymous'] ? 0 : 1)],
                ],
                [
                    ['text' => '消息预览【' . ($botUser['is_link_preview'] ? '是' : '否') . '】', 'callback_data' => 'c_c_s_d_m_p:null:' . ($botUser['is_link_preview'] ? 0 : 1)],
                ],
                [
                    ['text' => '消息静默发送【' . ($botUser['is_disable_notification'] ? '是' : '否') . '】', 'callback_data' => 'c_c_s_d_n:null:' . ($botUser['is_disable_notification'] ? 0 : 1)],
                ],
                [
                    ['text' => '消息禁止被转发和保存【' . ($botUser['is_protect_content'] ? '开启' : '不开启') . '】', 'callback_data' => 's_p_m_s_f_o:null:' . ($botUser['is_protect_content'] ? 0 : 1)],
                ],
                [
                    ['text' => '回到主页', 'callback_data' => 'home:null'],
                ],
            ],
        ];
    }
}
