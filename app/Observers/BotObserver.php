<?php

namespace App\Observers;

use App\Models\Bot;

class BotObserver
{
    /**
     * 处理Bot“创建”事件。
     *
     * @param Bot $bot
     * @return void
     */
    public function created(Bot $bot): void
    {
        $this->cache($bot);
    }

    /**
     * 处理Bot“更新”事件。
     *
     * @param Bot $bot
     * @return void
     */
    public function updated(Bot $bot): void
    {
        $this->cache($bot);
    }

    /**
     * 处理Bot“删除”事件。
     *
     * @param Bot $bot
     * @return void
     */
    public function deleted(Bot $bot): void
    {
        $this->clearCache($bot->id);
    }

    /**
     * 处理Bot“恢复”事件。
     *
     * @param Bot $bot
     * @return void
     */
    public function restored(Bot $bot): void
    {
        $this->cache($bot);
    }

    /**
     * 处理Bot“强制删除”事件。
     *
     * @param Bot $bot
     * @return void
     */
    public function forceDeleted(Bot $bot): void
    {
        $this->clearCache($bot->id);
    }


    private function cache(Bot $bot): void
    {
        // 使用模型的主键作为缓存的键
        $cacheKey = "bot_with_{$bot->id}";
        // 获取与 Bot 相关的 ReviewGroup 数据
        $bot->review_group;
        $bot->bot_command;
        // 将模型数据存入缓存，默认缓存时间为60分钟
        cache()->put($cacheKey, $bot, now()->addMinutes(60));
    }

    private function clearCache($id): void
    {
        $cacheKey = "bot_with_{$id}";
        cache()->forget($cacheKey);
    }
}
