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

    public function review_group(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReviewGroup::class, 'bot_id', 'id');
    }
}
