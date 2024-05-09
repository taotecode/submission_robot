<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class GetMeIdCommand extends Command
{
    //主要命令
    protected string $name = 'get_me_id';

    //命令描述
    protected string $description = '获取我的ID';

    public function handle()
    {
        $message = $this->getUpdate()->getMessage();
        if ($this->getUpdate()->getChat()->type != 'private') {
            $this->replyWithMessage([
                'text' => '<b>请在私聊中使用！</b>',
                'parse_mode' => 'HTML',
            ]);

            return 'ok';
        }

        $chatId = $this->getUpdate()->getChat()->id;
        $textData = "您的ID：<pre>{$chatId}</pre>";

        $this->replyWithMessage([
            'text' => $textData,
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message->id,
        ]);
    }
}
