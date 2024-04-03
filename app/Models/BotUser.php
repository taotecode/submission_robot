<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class BotUser extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'bot_users';

    protected $fillable = [
        'bot_id',
        'userId',
        'user_data',
    ];

    protected $casts = [
        'user_data' => 'json',
    ];

    public function bot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bot::class, 'bot_id', 'id');
    }
}
