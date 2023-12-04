<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class ReviewGroup extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'review_groups';

    //一对一管理到Bot
    public function bot()
    {
        return $this->hasOne(Bot::class, 'id', 'bot_id');
    }
}
