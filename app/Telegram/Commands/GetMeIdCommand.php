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
        if ($this->getUpdate()->getChat()->type != 'private') {
            $this->replyWithMessage([
                'text' => '*请在私聊中使用！*',
                'parse_mode' => 'MarkdownV2',
            ]);

            return 'ok';
        }

        $chatId = $this->getUpdate()->getChat()->id;
        $textData = "您的ID：`{$chatId}`";

        $this->replyWithMessage([
            'text' => $textData,
            'parse_mode' => 'MarkdownV2',
        ]);
    }
}
