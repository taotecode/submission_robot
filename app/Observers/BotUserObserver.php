<?php

namespace App\Observers;

use App\Models\BotUser;

class BotUserObserver
{
    /**
     * Handle the BotUser "created" event.
     *
     * @param  \App\Models\BotUser  $botUser
     * @return void
     */
    public function created(BotUser $botUser)
    {
        //
        $this->cache($botUser);
    }

    /**
     * Handle the BotUser "updated" event.
     *
     * @param  \App\Models\BotUser  $botUser
     * @return void
     */
    public function updated(BotUser $botUser)
    {
        //
        $this->cache($botUser);
    }

    /**
     * Handle the BotUser "deleted" event.
     *
     * @param  \App\Models\BotUser  $botUser
     * @return void
     */
    public function deleted(BotUser $botUser)
    {
        //
        $this->clearCache($botUser);
    }

    /**
     * Handle the BotUser "restored" event.
     *
     * @param  \App\Models\BotUser  $botUser
     * @return void
     */
    public function restored(BotUser $botUser)
    {
        //
        $this->cache($botUser);
    }

    /**
     * Handle the BotUser "force deleted" event.
     *
     * @param  \App\Models\BotUser  $botUser
     * @return void
     */
    public function forceDeleted(BotUser $botUser)
    {
        //
        $this->clearCache($botUser);
    }

    private function cache(BotUser $botUser): void
    {
        // 使用模型的主键作为缓存的键
        $cacheKey = "bot_user_{$botUser->bot_id}_{$botUser->user_id}";
        // 将模型数据存入缓存，默认缓存时间为60分钟
        cache()->put($cacheKey, $botUser, now()->addMinutes(60));
    }

    private function clearCache(BotUser $botUser): void
    {
        $cacheKey = "bot_user_{$botUser->bot_id}_{$botUser->user_id}";
        cache()->forget($cacheKey);
    }
}
