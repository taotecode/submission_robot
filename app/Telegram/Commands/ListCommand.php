<?php

namespace App\Telegram\Commands;

use App\Enums\KeyBoardData;
use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use Telegram\Bot\Commands\Command;

class ListCommand extends Command
{
    //主要命令
    protected string $name = 'list';
    //命令描述
    protected string $description = '待审核的稿件列表';

    /**
     * {@inheritDoc}
     */
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

        $inline_keyboard=[
            'inline_keyboard' => [
                [
                    [
                        'text' => '刷新 🔄',
                        'callback_data' => 'refresh_pending_manuscript_list',
                    ],
                ],
            ],
        ];

        $manuscript = (new \App\Models\Manuscript())->where('bot_id', $botInfo->id)->where('status', ManuscriptStatus::PENDING)->get();
        if (!$manuscript->isEmpty()){
            foreach ($manuscript as $item){
                $inline_keyboard['inline_keyboard'][] = [
                    [
                        'text' => "【".$item->text."】",
                        'callback_data' => 'show_pending_manuscript:'.$item->id,
                    ],
                ];
            }
        }

        $this->replyWithMessage([
            'text' => '待审核的稿件',
            'reply_markup' => json_encode($inline_keyboard),
            'reply_to_message_id' => $message->id,
        ]);

        return 'ok';
    }
}
