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
        'message_id' => 'json',
        'posted_by' => 'json',
        'data' => 'json',
        'appendix' => 'json',
        'approved' => 'json',
        'reject' => 'json',
        'one_approved' => 'json',
        'one_reject' => 'json',
    ];
}
