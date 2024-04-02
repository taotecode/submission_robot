<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Auditor extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'auditors';

    protected $fillable = ['userId', 'name', 'role'];

    //设置json字段
    protected $casts = [
        'role' => 'array',
    ];
}
