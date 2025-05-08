<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class BotCommand extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'bot_commands';

    protected $fillable = [
        'bot_id',
        'command',
        'scope',
        'chat_id',
        'description',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];

    public function bot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bot::class, 'bot_id', 'id');
    }
}
