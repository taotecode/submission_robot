<?php

namespace App\Telegram\Commands;

use App\Enums\AuditorRole;
use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\SubmissionUser;
use App\Services\CallBackQuery\AuditorRoleCheckService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

class BlackCommand extends Command
{
    use AuditorRoleCheckService;

    //主要命令
    protected string $name = 'black';

    //命令描述
    protected string $description = '将用户添加至黑名单';

    protected string $pattern = '{user_id}';

    public function handle(): string
    {
        $chat = $this->getUpdate()->getChat();
        $message = $this->getUpdate()->getMessage();
        $from = $message->from;

        if (! in_array($this->getUpdate()->getChat()->type, ['group', 'supergroup'])) {
            $this->replyWithMessage([
                'text' => "<b>请在群组中使用！</b>",
                'parse_mode' => 'HTML',
                'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
                'reply_to_message_id' => $message->id,
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
                'reply_to_message_id' => $message->id,
            ]);

            return 'ok';
        }

        $user_id = $this->getArguments()['user_id']??'';
        if (empty($user_id)){
            $this->replyWithMessage([
                'text' => "<b>请填写用户ID！</b>,如：<pre>/black 12345678</pre>",
                'parse_mode' => 'HTML',
                'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
                'reply_to_message_id' => $message->id,
            ]);
            return 'ok';
        }

        if ($this->baseCheck($this->getTelegram(), $chat->id, $from->id, $botInfo->review_group->id,true,$message->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($this->getTelegram(), $chat->id, $from->id, [
                AuditorRole::ADD_BLACK,
            ],true,$message->id) !== true) {
            return 'ok';
        }

        $submissionUser = (new SubmissionUser)->firstOrCreate([
            'bot_id' => $botInfo->id,
            'userId' => $user_id,
        ], [
            'type' => SubmissionUserType::BLACK,
            'bot_id'=>$botInfo->id,
            'userId' => $user_id,
            'name' => "未知",
        ]);

        if ($submissionUser->type!= SubmissionUserType::BLACK){
            $submissionUser->type = SubmissionUserType::BLACK;
            $submissionUser->save();
        }

        $this->replyWithMessage([
            'text' => "<b>用户ID：{$user_id} 已加入黑名单！</b>",
            'parse_mode' => 'HTML',
            'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
            'reply_to_message_id' => $message->id,
        ]);

        return 'ok';
    }
}
