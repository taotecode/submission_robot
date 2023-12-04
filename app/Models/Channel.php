<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasDateTimeFormatter;

    protected $fillable = [
        'name',
        'appellation',
        'created_at',
        'updated_at',
    ];
}
