<?php

namespace App\Telegram\Commands;

use App\Enums\ManuscriptStatus;
use App\Models\Bot;
use Telegram\Bot\Commands\Command;

class SearchCommand extends Command
{
    //主要命令
    protected string $name = 's';
    //命令描述
    protected string $description = '检索稿件';

    protected string $pattern = '{keyword}';

    /**
     * {@inheritDoc}
     */
    public function handle(): string
    {
        $message = $this->getUpdate()->getMessage();

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

        $keyword = $this->getArguments()['keyword']??'';
        if (empty($keyword)){
            $this->replyWithMessage([
                'text' => "<b>请填写关键字！</b>,如：<pre>/s 关键字</pre>",
                'parse_mode' => 'HTML',
                'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
                'reply_to_message_id' => $message->id,
            ]);
            return 'ok';
        }

        $inline_keyboard=[
            'inline_keyboard' => []
        ];

        $manuscript = (new \App\Models\Manuscript())
            ->where('bot_id', $botInfo->id)
            ->where('status', ManuscriptStatus::APPROVED)
//            ->where('text', 'like', '%'.$keyword.'%')
//            ->where('data', 'like', '%'.$keyword.'%')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'page', 1);

        if ($manuscript->isEmpty()){
            $this->replyWithMessage([
                'text' => "<b>没有找到相关稿件！</b>",
                'parse_mode' => 'HTML',
                'reply_markup'=>json_encode(['remove_keyboard'=>true,'selective'=>false]),
                'reply_to_message_id' => $message->id,
            ]);
            return 'ok';
        }

        $manuscript->each(function ($item) use (&$inline_keyboard){
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => $item->text, 'callback_data' => 'manuscript_search_show_link:'.$item->id]
            ];
        });

        if ($manuscript->lastPage() > 1) {
            $inline_keyboard['inline_keyboard'][] = [
                ['text' => '下一页', 'callback_data' => 'manuscript_search_page:next:'.$keyword.':'.$manuscript->currentPage()+1],
            ];
        }

        $this->replyWithMessage([
            'text' => "<b>检索结果：</b>",
            'parse_mode' => 'HTML',
            'reply_markup'=>json_encode($inline_keyboard),
            'reply_to_message_id' => $message->id,
        ]);
        return 'ok';
    }
}
