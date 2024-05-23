<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class ReviewGroup extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'review_groups';

    protected static function booted(): void
    {
        static::saved(function ($reviewGroup) {
            self::clearRelatedBotCache($reviewGroup);
        });

        static::deleted(function ($reviewGroup) {
            self::clearRelatedBotCache($reviewGroup);
        });
    }

    protected static function clearRelatedBotCache($reviewGroup): void
    {
        // 假设 ReviewGroup 通过 review_group_id 关联到 Bot
        $bot = $reviewGroup->bot; // 获取关联的 Bot 模型实例

        if ($bot) {
            $cacheKey = "bot_with_review_group_{$bot->id}";
            cache()->forget($cacheKey);
        }
    }

    //一对一管理到Bot
    public function bot()
    {
        return $this->hasOne(Bot::class, 'id', 'bot_id');
    }
}
