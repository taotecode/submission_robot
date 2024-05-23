<?php

namespace App\Telegram\Commands;

use App\Enums\CacheKey;
use App\Admin\Repositories\Bot;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    //主要命令
    protected string $name = 'start';

    //或者
    protected array $aliases = [

    ];

    //命令描述
    protected string $description = 'Start Command to get you started';

    public function handle(): void
    {

        $botId = request()->route('id');
        $botInfo = (new Bot())->findInfo($botId);

        $message = $this->getUpdate()->getMessage();
        $chatId = $this->getUpdate()->getChat()->id;
        Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();
        Cache::tags(CacheKey::Complaint.'.'.$chatId)->flush();
        Cache::tags(CacheKey::Suggestion.'.'.$chatId)->flush();

        //回复消息
        $this->replyWithMessage([
            'text' => get_config('command.start'),
            'reply_markup' => json_encode(service_isOpen_check_return_keyboard($botInfo)),
            'reply_to_message_id' => $message->id,
        ]);
    }
}
