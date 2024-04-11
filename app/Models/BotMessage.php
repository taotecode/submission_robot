<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotMessage extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'bot_messages';

    protected $fillable = [
        'bot_id',
        'user_id',
        'user_data',
        'data',
    ];

    protected $casts = [
        'user_data' => 'json',
        'data' => 'json',
    ];

    public function bot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bot::class, 'bot_id', 'id');
    }
}
