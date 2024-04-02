<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class GetGroupIdCommand extends Command
{
    //主要命令
    protected string $name = 'get_group_id';

    //命令描述
    protected string $description = '获取群组ID';

    public function handle()
    {
        if (! in_array($this->getUpdate()->getChat()->type, ['group', 'supergroup'])) {
            $this->replyWithMessage([
                'text' => "<b>请在群组中使用！</b>",
                'parse_mode' => 'HTML',
            ]);

            return 'ok';
        }

        $chatId = $this->getUpdate()->getChat()->id;
        $chatTitle = $this->getUpdate()->getChat()->title;

        $botData = $this->getTelegram()->getMe();

        $chatMember = $this->getTelegram()->getChatMember([
            'chat_id' => $chatId,
            'user_id' => $botData->id,
        ]);

        $isAdmin = '非管理员';

        if ($chatMember->status === 'administrator' || $chatMember->status === 'creator') {
            $isAdmin = '是管理员';
        }

        $textData = "群组名称：<pre>{$chatTitle}</pre> \r\n 群组ID ：<pre>{$chatId}</pre> \r\n 机器人是否是管理员：{$isAdmin}";

        $this->replyWithMessage([
            'text' => $textData,
            'parse_mode' => 'HTML',
        ]);

        return 'ok';
    }
}
