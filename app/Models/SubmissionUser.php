<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class SubmissionUser extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'submission_user';

    protected $fillable = [
        'bot_id',
        'type',
        'user_id',
        'user_data',
        'name',
    ];

    protected $casts = [
        'user_data' => 'json',
    ];
}
