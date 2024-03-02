<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    use HasDateTimeFormatter;

    protected $casts = [
        'tail_content_button' => 'json',
    ];

    public function review_group()
    {
        return $this->hasOne(ReviewGroup::class, 'bot_id', 'id');
    }

    public function channel()
    {
        return $this->hasOne(Channel::class, 'id', 'channel_id');
    }
}
