<?php

namespace App\Services\CallBackQuery;

use App\Models\BotUser;

class SettingsServices
{
    public function index($telegram, $botInfo, $command, $commandArray, $chatId, $messageId, $callbackQueryId)
    {
        $status = $commandArray[2];
        $settingsMap = [
            'c_c_s_anonymous' => 'is_anonymous',
            'c_c_s_d_m_p' => 'is_link_preview',
            'c_c_s_d_n' => 'is_disable_notification',
            's_p_m_s_f_o' => 'is_protect_content'
        ];

        if (!isset($settingsMap[$command])) {
            return 'error';
        }

        $type = $settingsMap[$command];
        return $this->updateSetting($telegram, $botInfo, $type, $status, $chatId, $messageId, $callbackQueryId);
    }

    private function updateSetting($telegram, $botInfo, $type, $status, $chatId, $messageId, $callbackQueryId)
    {
        // 更新信息
        $botUser = BotUser::where('bot_id', $botInfo->id)->where('user_id', $chatId)->first();
        $botUser->$type = $status;
        $botUser->save();

        $settingsText = $this->getSettingsText($botUser, $status, $type);

        $replyMarkup = json_encode($this->createReplyMarkup($botUser));

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
    }

    private function getSettingsText($botUser, $status, $type)
    {
        $texts = [
            'is_anonymous' => $status ? '匿名' : '不匿名',
            'is_link_preview' => $status ? '是' : '否',
            'is_disable_notification' => $status ? '是' : '否',
            'is_protect_content' => $status ? '开启' : '不开启',
        ];

        return $texts[$type] ?? '';
    }

    private function createReplyMarkup($botUser)
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '投稿身份【' . ($botUser->is_anonymous ? '匿名' : '不匿名') . '】', 'callback_data' => 'c_c_s_anonymous:null:' . ($botUser->is_anonymous ? 0 : 1)],
                ],
                [
                    ['text' => '消息预览【' . ($botUser->is_link_preview ? '是' : '否') . '】', 'callback_data' => 'c_c_s_d_m_p:null:' . ($botUser->is_link_preview ? 0 : 1)],
                ],
                [
                    ['text' => '消息静默发送【' . ($botUser->is_disable_notification ? '是' : '否') . '】', 'callback_data' => 'c_c_s_d_n:null:' . ($botUser->is_disable_notification ? 0 : 1)],
                ],
                [
                    ['text' => '消息禁止被转发和保存【' . ($botUser->is_protect_content ? '开启' : '不开启') . '】', 'callback_data' => 's_p_m_s_f_o:null:' . ($botUser->is_protect_content ? 0 : 1)],
                ],
            ],
        ];
    }
}
