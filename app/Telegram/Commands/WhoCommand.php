<?php

namespace App\Telegram\Commands;

use App\Enums\AuditorRole;
use App\Models\Bot;
use App\Services\CallBackQuery\AuditorRoleCheckService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

class WhoCommand extends Command
{
    use AuditorRoleCheckService;

    //主要命令
    protected string $name = 'who';

    //命令描述
    protected string $description = '获取用户投稿信息';

    public function handle()
    {
        $chat = $this->getUpdate()->getChat();
        $message = $this->getUpdate()->getMessage();
        $from = $message->from;
        $replyToMessage=$message->replyToMessage;

        if (! in_array($this->getUpdate()->getChat()->type, ['group', 'supergroup'])) {
            $this->replyWithMessage([
                'text' => "<b>请在群组中使用！</b>",
                'parse_mode' => 'HTML',
                'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
            ]);

            return 'ok';
        }

        $botData = $this->getTelegram()->getMe();
        $botInfo = (new Bot())->where('name', $botData->username)->first();
        if (!$botInfo){
            $this->replyWithMessage([
                'text' => "<b>请先前往后台添加机器人！或后台机器人的用户名没有设置正确！</b>",
                'parse_mode' => 'HTML',
                'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
            ]);

            return 'ok';
        }

        if ($this->baseCheck($this->getTelegram(), $chat->id, $from->id, $botInfo->review_group->id,true,$message->id) !== true) {
            return 'ok';
        }

        if (empty($replyToMessage)) {
            $this->replyWithMessage([
                'text' => "<b>请回复用户投稿的稿件消息再使用本命令！</b>",
                'parse_mode' => 'HTML',
                'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
            ]);

            return 'ok';
        }

        $replyMarkup=$replyToMessage->replyMarkup;

        Log::info('message: ',$replyMarkup->toArray());
        return 'ok';
    }
}
