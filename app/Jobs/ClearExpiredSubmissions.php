<?php

namespace App\Jobs;

use App\Enums\CacheKey;
use App\Models\Bot;
use App\Services\SendTelegramMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;

class ClearExpiredSubmissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SendTelegramMessageService, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $botList = (new Bot)->get();
        foreach ($botList as $bot) {
            $cacheKey = CacheKey::SubmissionUserList.':'.$bot->id;
            if (! Cache::has($cacheKey)) {
                continue;
            }
            $telegram = new Api($bot->token);
            $userList = Cache::get($cacheKey);
            foreach ($userList as $chatId => $expireTime) {
                //已经过期
                if ($expireTime < time()) {
                    Cache::tags(CacheKey::Submission.'.'.$chatId)->flush();
                    unset($userList[$chatId]);

                    //给用户发送投稿草稿失效通知
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'text' => get_config('submission.expired'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(service_isOpen_check_return_keyboard($bot)),
                    ]);

                    continue;
                }
                //还有一小时过期
                if ($expireTime - time() < 3600) {
                    $this->sendTelegramMessage($telegram, 'sendMessage', [
                        'chat_id' => $chatId,
                        'text' => get_config('submission.expired_in_one_hour'),
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }
}
