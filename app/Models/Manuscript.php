<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Manuscript extends Model
{
    use HasDateTimeFormatter;

    /**
     * @var array|mixed|string|null
     */
    protected $fillable = [
        'bot_id',
        'channel_id',
        'message_id',
        'type',
        'text',
        'posted_by',
        'posted_by_id',
        'is_anonymous',
        'data',
        'appendix',
        'approved',
        'reject',
        'one_approved',
        'one_reject',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'posted_by' => 'json',
        'data' => 'json',
        'appendix' => 'json',
        'approved' => 'json',
        'reject' => 'json',
        'one_approved' => 'json',
        'one_reject' => 'json',
    ];

    const TYPE_TEXT = 'text';

    public function bot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bot::class, 'bot_id', 'id');
    }

    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }
}
