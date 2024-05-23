<?php

namespace App\Telegram\Commands;

use App\Enums\SubmissionUserType;
use App\Models\Bot;
use App\Models\Manuscript;
use App\Models\SubmissionUser;
use App\Services\CallBackQuery\AuditorRoleCheckService;
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
        $replyToMessage = $message->replyToMessage;

        if (! in_array($this->getUpdate()->getChat()->type, ['group', 'supergroup'])) {
            $this->replyWithMessage([
                'text' => '<b>请在群组中使用！</b>',
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true, 'selective' => false]),
                'reply_to_message_id' => $message->id,
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

        if ($this->baseCheck($this->getTelegram(), $chat->id, $from->id, $botInfo->review_group->id, true, $message->id) !== true) {
            return 'ok';
        }

        if (empty($replyToMessage)) {
            $this->replyWithMessage([
                'text' => '<b>请回复用户投稿的稿件消息再使用本命令！</b>',
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true, 'selective' => false]),
                'reply_to_message_id' => $message->id,
            ]);

            return 'ok';
        }

        $replyMarkup = $replyToMessage->replyMarkup->toArray();
        $command = $replyMarkup['inline_keyboard'][0][0]['callback_data'];
        if (empty($command)) {
            $this->replyWithMessage([
                'text' => '<b>请回复用户投稿的稿件消息再使用本命令！</b>',
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true, 'selective' => false]),
                'reply_to_message_id' => $message->id,
            ]);

            return 'ok';
        }
        $commandArray = explode(':', $command);
        if (count($commandArray) > 1) {
            $manuscriptId = $commandArray[1];
            $manuscript = Manuscript::find($manuscriptId);
        } else {
            $this->replyWithMessage([
                'text' => '<b>请回复用户投稿的稿件消息再使用本命令！</b>',
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true, 'selective' => false]),
                'reply_to_message_id' => $message->id,
            ]);

            return 'ok';
        }

        $submissionUser = $manuscript->posted_by;
        if (empty($submissionUser)) {
            $this->replyWithMessage([
                'text' => '<b>未找到投稿用户信息！</b>',
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true, 'selective' => false]),
                'reply_to_message_id' => $message->id,
            ]);

            return 'ok';
        }

        $submissionUserInfo = (new SubmissionUser)->firstOrCreate([
            'bot_id' => $botInfo->id,
            'user_id' => $submissionUser['id'],
        ], [
            'type' => SubmissionUserType::NORMAL,
            'bot_id' => $botInfo->id,
            'user_id' => $submissionUser['id'],
            'user_data' => $submissionUser,
            'name' => '未知',
        ]);

        $text = '用户ID：<code>'.$submissionUser['id']."</code> \r\n";
        if (! empty($submissionUser['username'])) {
            $text .= '用户名：<code>'.$submissionUser['username']."</code> \r\n";
        }

        if (! empty($submissionUser['first_name'])) {
            $text .= '姓名：<code>'.$submissionUser['first_name']."</code> \r\n";
        }

        if (! empty($submissionUser['last_name'])) {
            $text .= '姓氏：<code>'.$submissionUser['last_name']."</code> \r\n";
        }

        $text .= '用户身份：<code>'.SubmissionUserType::MAP[$submissionUserInfo->type]."</code> \r\n";

        $inline_keyboard = [
            'inline_keyboard' => [
            ],
        ];

        foreach (SubmissionUserType::MAP as $key => $value) {
            if ($key == $submissionUserInfo->type) {
                continue;
            }
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => '设置为'.$value.'用户', 'callback_data' => 'set_submission_user_type:'.$manuscriptId.':'.$key.':'.$submissionUser['id']],
            ];
        }

        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inline_keyboard),
            'reply_to_message_id' => $message->id,
        ]);

        return 'ok';
    }
}
