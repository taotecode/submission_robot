<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    use HasDateTimeFormatter;

    protected $casts = [
        'tail_content_button' => 'json',
        'channel_ids' => 'json',
    ];

    protected static function booted(): void
    {
        static::saved(function ($bot) {
            self::clearBotCache($bot->id);
        });

        static::deleted(function ($bot) {
            self::clearBotCache($bot->id);
        });
    }

    protected static function clearBotCache($id): void
    {
        $cacheKey = "bot_with_review_group_{$id}";
        cache()->forget($cacheKey);
    }

    private function updateBotCache($id, $data = null): void
    {
        $cacheKey = "bot_with_review_group_{$id}";
        if ($data) {
            cache()->put($cacheKey, $data, now()->addWeek());
        } else {
            cache()->forget($cacheKey);
        }
    }

    public function review_group(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReviewGroup::class, 'bot_id', 'id');
    }
}
