<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Auditor extends Model
{
    use HasDateTimeFormatter;

    //设置json字段
    protected $casts = [
        'role' => 'array',
    ];
}
