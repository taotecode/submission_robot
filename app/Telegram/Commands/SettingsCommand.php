<?php

namespace App\Telegram\Commands;

use App\Models\Bot;
use Telegram\Bot\Commands\Command;

class SettingsCommand extends Command
{
//主要命令
    protected string $name = 'settings';

    //命令描述
    protected string $description = '我的设置';

    /**
     * {@inheritDoc}
     */
    public function handle(): string
    {
        $message = $this->getUpdate()->getMessage();

        if ($this->getUpdate()->getChat()->type !== 'private') {
            $this->replyWithMessage([
                'text' => '<b>请在私聊中使用！</b>',
                'parse_mode' => 'HTML',
            ]);

            return 'ok';
        }

        $botData = $this->getTelegram()->getMe();
        $botInfo = (new Bot())->where('name', $botData->username)->first();
        if (! $botInfo) {
            $this->replyWithMessage([
                'text' => '<b>请先前往后台添加机器人！或后台机器人的用户名没有设置正确！</b>',
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true, 'selective' => false]),
                'reply_to_message_id' => $message->id,
            ]);

            return 'ok';
        }

        $this->replyWithMessage([
            'text' => "设置",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '投稿身份【匿名】', 'callback_data' => 'a1'],
                    ],
                    [
                        ['text' => '消息预览【否】', 'callback_data' => 'a1'],
                    ],
                    [
                        ['text' => '消息静默发送【是】', 'callback_data' => 'a1'],
                    ],
                    [
                        ['text' => '消息来源【从不输入】', 'callback_data' => 'a1'],
                    ],
                ],
            ]),
            'reply_to_message_id' => $message->id,
        ]);

        return 'ok';
    }
}
