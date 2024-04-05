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
        'message_id',
        'type',
        'text',
        'posted_by',
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

    public function bot()
    {
        return $this->belongsTo(Bot::class,'id', 'bot_id');
    }
}
