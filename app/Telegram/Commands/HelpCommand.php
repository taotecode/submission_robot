<?php

namespace App\Telegram\Commands;

use App\Enums\KeyBoardData;
use Telegram\Bot\Commands\Command;

class HelpCommand extends Command
{
    //主要命令
    protected string $name = 'help';

    //或者
    protected array $aliases = [

    ];

    //命令描述
    protected string $description = 'Help Command to get you started';

    /**
     * {@inheritDoc}
     */
    public function handle(): void
    {
        $message = $this->getUpdate()->getMessage();
        dump(KeyBoardData::$START);
        $this->replyWithMessage([
            'text' => '您可以使用底部的操作键盘快速交互，或者发送 /help 命令查看详细的功能介绍',
            'reply_markup' => json_encode(KeyBoardData::$START),
            'reply_to_message_id' => $message->id,
        ]);
    }
}
