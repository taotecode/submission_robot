<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Auditor extends Model
{
    use HasDateTimeFormatter;

    const ROLE = [
        '1' => '快捷通过审核',
        '2' => '快捷拒绝审核',
        '3' => '私聊投稿人',
    ];

    //设置json字段
    protected $casts = [
        'role' => 'array',
    ];
}
