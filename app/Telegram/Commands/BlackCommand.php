<?php

namespace App\Telegram\Commands;

use App\Models\Bot;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

class BlackCommand extends Command
{
    //主要命令
    protected string $name = 'black';

    //命令描述
    protected string $description = '获取群组ID';

    protected string $pattern = '{user_id}';

    public function handle()
    {
        if (! in_array($this->getUpdate()->getChat()->type, ['group', 'supergroup'])) {
            $this->replyWithMessage([
                'text' => "<b>请在群组中使用！</b>",
                'parse_mode' => 'HTML',
            ]);

            return 'ok';
        }

        $botData = $this->getTelegram()->getMe();
        $botInfo = (new Bot())->where('name', $botData->username)->first();
        if (!$botInfo){
            $this->replyWithMessage([
                'text' => "<b>请先前往后台添加机器人！或后台机器人的用户名没有设置正确！</b>",
                'parse_mode' => 'HTML',
            ]);

            return 'ok';
        }

        $user_id = $this->getArguments()['user_id'];
        Log::info('用户ID', [$user_id]);

        Log::info('加入黑名单', $this->getArguments());
        return 'ok';
    }
}
