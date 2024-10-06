<?php

namespace App\Telegram\Commands;

use App\Models\Bot;
use App\Models\BotUser;
use Telegram\Bot\Commands\Command;

class SettingsCommand extends Command
{
    protected string $name = 'settings';
    protected string $description = '我的设置';

    public function handle()
    {
        $message = $this->getUpdate()->getMessage();
        $chat = $this->getUpdate()->getChat();

        if ($chat->type !== 'private') {
            return $this->sendPrivateUsageMessage();
        }

        $botInfo = $this->getBotInfo();
        if (!$botInfo) {
            return $this->sendSetupReminder($message->id);
        }

        $botUser = $this->getBotUser($botInfo->id, $chat->id);
        return $this->sendSettingsMenu($botUser, $message->id);
    }

    private function sendPrivateUsageMessage()
    {
        $this->replyWithMessage([
            'text' => '<b>请在私聊中使用！</b>',
            'parse_mode' => 'HTML',
        ]);
        return 'ok';
    }

    private function getBotInfo()
    {
        $botData = $this->getTelegram()->getMe();
        return Bot::where('name', $botData->username)->first();
    }

    private function sendSetupReminder($messageId)
    {
        $this->replyWithMessage([
            'text' => '<b>请先前往后台添加机器人！或后台机器人的用户名没有设置正确！</b>',
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['remove_keyboard' => true]),
            'reply_to_message_id' => $messageId,
        ]);
        return 'ok';
    }

    private function getBotUser($botId, $chatId)
    {
        return BotUser::where('bot_id', $botId)->where('user_id', $chatId)->first();
    }

    private function sendSettingsMenu($botUser, $messageId)
    {
        $settings = [
            'anonymous' => $botUser->is_anonymous ? '匿名' : '不匿名',
            'link_preview' => $botUser->is_link_preview ? '是' : '否',
            'disable_notification' => $botUser->is_disable_notification ? '是' : '否',
            'protect_content' => $botUser->is_protect_content ? '开启' : '不开启',
        ];

        $callbackData = [
            'is_anonymous' => $botUser->is_anonymous ? 0 : 1,
            'is_link_preview' => $botUser->is_link_preview ? 0 : 1,
            'is_disable_notification' => $botUser->is_disable_notification ? 0 : 1,
            'is_protect_content' => $botUser->is_protect_content ? 0 : 1,
        ];

        $replyMarkup = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "投稿身份【{$settings['anonymous']}】", 'callback_data' => "c_c_s_anonymous:null:{$callbackData['is_anonymous']}"],
                ],
                [
                    ['text' => "消息预览【{$settings['link_preview']}】", 'callback_data' => "c_c_s_d_m_p:null:{$callbackData['is_link_preview']}"],
                ],
                [
                    ['text' => "消息静默发送【{$settings['disable_notification']}】", 'callback_data' => "c_c_s_d_n:null:{$callbackData['is_disable_notification']}"],
                ],
                [
                    ['text' => "消息禁止被转发和保存【{$settings['protect_content']}】", 'callback_data' => "s_p_m_s_f_o:null:{$callbackData['is_protect_content']}"],
                ],
            ],
        ]);

        $this->replyWithMessage([
            'text' => "设置",
            'parse_mode' => 'HTML',
            'reply_markup' => $replyMarkup,
            'reply_to_message_id' => $messageId,
        ]);

        return 'ok';
    }
}
