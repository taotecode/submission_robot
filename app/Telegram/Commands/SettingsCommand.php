<?php

namespace App\Telegram\Commands;

use App\Models\Bot;
use App\Models\BotUser;
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

        $chatId=$this->getUpdate()->getChat()->id;

        $botUser=(new BotUser())->where('bot_id', $botInfo->id)->where('user_id', $chatId)->first();


        $type_text=($botUser->is_anonymous==1)?'匿名':'不匿名';
        $type_text1=($botUser->is_link_preview==1)?'是':'否';
        $type_text2=($botUser->is_disable_notification==1)?'是':'否';
        $type_text3=($botUser->is_protect_content==1)?'主动输入':'从不输入';

        $this->replyWithMessage([
            'text' => "设置",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '投稿身份【'.$type_text.'】', 'callback_data' => 'c_c_s_anonymous:null:'.$botUser->is_anonymous],
                    ],
                    [
                        ['text' => '消息预览【'.$type_text1.'】', 'callback_data' => 'c_c_s_d_m_p:null:'.$botUser->is_anonymous],
                    ],
                    [
                        ['text' => '消息静默发送【'.$type_text2.'】', 'callback_data' => 'c_c_s_d_n:null:'.$botUser->is_anonymous],
                    ],
                    [
                        ['text' => '消息来源【'.$type_text3.'】', 'callback_data' => 's_p_m_s_f_o:null:'.$botUser->is_anonymous],
                    ],
                ],
            ]),
            'reply_to_message_id' => $message->id,
        ]);

        return 'ok';
    }
}
