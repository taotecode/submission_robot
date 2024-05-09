<?php

namespace App\Telegram\Commands;

use App\Models\Bot;
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

        $botId = request()->route('id');

        $botInfo = (new Bot())->find($botId);

        $this->replyWithMessage([
            'text' => get_config('command.help'),
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
            'reply_to_message_id' => $message->id,
        ]);
    }
}
