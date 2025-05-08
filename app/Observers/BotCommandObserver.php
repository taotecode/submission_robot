<?php

namespace App\Observers;

use App\Models\Bot;
use App\Models\BotCommand;

class BotCommandObserver
{
    /**
     * Handle the BotCommand "created" event.
     *
     * @param  \App\Models\BotCommand  $botCommand
     * @return void
     */
    public function created(BotCommand $botCommand)
    {
        //
        $this->cache($botCommand);
    }

    /**
     * Handle the BotCommand "updated" event.
     *
     * @param  \App\Models\BotCommand  $botCommand
     * @return void
     */
    public function updated(BotCommand $botCommand)
    {
        //
        $this->cache($botCommand);
    }

    /**
     * Handle the BotCommand "deleted" event.
     *
     * @param  \App\Models\BotCommand  $botCommand
     * @return void
     */
    public function deleted(BotCommand $botCommand)
    {
        //
        $this->cache($botCommand);
    }

    /**
     * Handle the BotCommand "restored" event.
     *
     * @param  \App\Models\BotCommand  $botCommand
     * @return void
     */
    public function restored(BotCommand $botCommand)
    {
        //
        $this->cache($botCommand);
    }

    /**
     * Handle the BotCommand "force deleted" event.
     *
     * @param  \App\Models\BotCommand  $botCommand
     * @return void
     */
    public function forceDeleted(BotCommand $botCommand)
    {
        //
        $this->cache($botCommand);
    }

    private function cache(BotCommand $botCommand): void
    {
        $bot=Bot::find($botCommand->bot_id);
        // 使用模型的主键作为缓存的键
        $cacheKey = "bot_with_{$botCommand->bot_id}";
        // 获取与 Bot 相关的 ReviewGroup 数据
        $bot->review_group;
        $bot->bot_command;
        // 将模型数据存入缓存，默认缓存时间为60分钟
        cache()->put($cacheKey, $bot, now()->addMinutes(60));
    }
}
